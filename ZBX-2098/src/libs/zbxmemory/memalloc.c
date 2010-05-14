/*
** ZABBIX
** Copyright (C) 2000-2010 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/

#include "common.h"
#include "mutexs.h"
#include "ipc.h"
#include "log.h"

#include "memalloc.h"

/******************************************************************************
 *                                                                            *
 *                     Some information on memory layout                      *
 *                  ---------------------------------------                   *
 *                                                                            *
 *                                                                            *
 * (*) chunk: a contiguous piece of memory that is either free or used        *
 *                                                                            *
 *                                                                            *
 *                    +-------- size of + --------------+                     *
 *                    |       (4 bytes) |               |                     *
 *                    |                 v               |                     *
 *                    |                                 |                     *
 *                    |    +- allocatable memory --+    |                     *
 *                    |    | (user data goes here) |    |                     *
 *                    v    v                       v    v                     *
 *                                                                            *
 *                |--------|----------------...----|--------|                 *
 *                                                                            *
 *                ^        ^                       ^        ^                 *
 *            4-aligned    |                       |    4-aligned             *
 *                                                                            *
 *                     8-aligned               8-aligned                      *
 *                                                                            *
 *                                                                            *
 *     when a chunk is used, `size' fields have MEM_FLG_USED bit set          *
 *                                                                            *
 *     when a chunk is free, the first 2 * ZBX_PTR_SIZE bytes of allocatable  *
 *     memory contain pointers to the previous and next chunks, in that order *
 *                                                                            *
 *     notes:                                                                 *
 *                                                                            *
 *         - user data is nicely 8-aligned                                    *
 *                                                                            *
 *         - size is kept on both left and right ends for quick merging       *
 *           (when freeing a chunk, we can quickly see if the previous        *
 *           and next chunks are free, those will not have MEM_FLG_USED)      *
 *                                                                            *
 *                                                                            *
 * (*) free chunks are stored in a doubly-linked list                         *
 *                                                                            *
 *     a typical situation is thus as follows (1 used chunk, 2 free chunks)   *
 *                                                                            *
 *                                                                            *
 *  +--------------------------- shared memory ----------------------------+  *
 *  |                         (can be misaligned)                          |  *
 *  |                                                                      |  *
 *  |                                                                      |  *
 *  |  +------ chunk A ------+------ chunk B -----+------ chunk C ------+  |  *
 *  |  |       (free)        |       (used)       |       (free)        |  |  *
 *  |  |                     |                    |                     |  |  *
 *  v  v                     v                    v                     v  v  *
 *           prevnext              user data            prevnext              *
 *  #--|----|--------...|----|----|---....---|----|----|--------...|----|--#  *
 *           NULL  |                                     |  NULL              *
 *     ^           |                              ^      |              ^     *
 *     |           |                              |      |              |     *
 *     |           +------------------------------+      |              |     *
 *     |                                                 |              |     *
 *     +-------------------------------------------------+              |     *
 *     |                                                                |     *
 *                                                                            *
 *  lo_bound             `size' fields in chunk B                   hi_bound  *
 *  (aligned)            have MEM_FLG_USED bit set                 (aligned)  *
 *                                                                            *
 ******************************************************************************/

#define LOCK_INFO	if (info->use_lock) zbx_mutex_lock(&info->mem_lock)
#define UNLOCK_INFO	if (info->use_lock) zbx_mutex_unlock(&info->mem_lock)

static void	*ALIGN8(void *ptr);

static uint32_t	mem_proper_alloc_size(uint32_t size);

static void	mem_set_chunk_size(void *chunk, uint32_t size);
static void	mem_set_used_chunk_size(void *chunk, uint32_t size);

static void	*mem_get_prev_chunk(void *chunk);
static void	mem_set_prev_chunk(void *chunk, void *prev);
static void	*mem_get_next_chunk(void *chunk);
static void	mem_set_next_chunk(void *chunk, void *next);
static void	**mem_ptr_to_prev_field(void *chunk);
static void	**mem_ptr_to_next_field(void *chunk, void **first_chunk);

static void	*__mem_malloc(zbx_mem_info_t *info, uint32_t size);
static void	*__mem_realloc(zbx_mem_info_t *info, void *old, uint32_t size);
static void	__mem_free(zbx_mem_info_t *info, void *ptr);

#define MEM_SIZE_FIELD	sizeof(uint32_t)

#define MEM_FLG_USED	(((uint32_t)1)<<31)

#define FREE_CHUNK(ptr)	(((*(uint32_t *)(ptr)) & MEM_FLG_USED) == 0)
#define CHUNK_SIZE(ptr) ((*(uint32_t *)(ptr)) & ~MEM_FLG_USED)

#define	MEM_MIN_SIZE	128
#define MEM_MAX_SIZE	(1<<30)	/* 1 GB */

#define MEM_MIN_ALLOC	16	/* should be a multiple of 8 and at least (2 * ZBX_PTR_SIZE) */

/* helper functions */

static void	*ALIGN8(void *ptr)
{
	return (void *)((uintptr_t)(ptr + 7) & (uintptr_t)~7);
}

static uint32_t	mem_proper_alloc_size(uint32_t size)
{
	if (size >= MEM_MIN_ALLOC)
		return size + (8 - size % 8) % 8;	/* allocate in multiples of 8... */
	else
		return MEM_MIN_ALLOC;			/* ...and at least MEM_MIN_ALLOC */
}

static void	mem_set_chunk_size(void *chunk, uint32_t size)
{
	*(uint32_t *)chunk = size;
	*(uint32_t *)(chunk + MEM_SIZE_FIELD + size) = size;
}

static void	mem_set_used_chunk_size(void *chunk, uint32_t size)
{
	*(uint32_t *)chunk = MEM_FLG_USED | size;
	*(uint32_t *)(chunk + MEM_SIZE_FIELD + size) = MEM_FLG_USED | size;
}

static void	*mem_get_prev_chunk(void *chunk)
{
	return *(void **)(chunk + MEM_SIZE_FIELD);
}

static void	mem_set_prev_chunk(void *chunk, void *prev)
{
	*(void **)(chunk + MEM_SIZE_FIELD) = prev;
}

static void	*mem_get_next_chunk(void *chunk)
{
	return *(void **)(chunk + MEM_SIZE_FIELD + ZBX_PTR_SIZE);
}

static void	mem_set_next_chunk(void *chunk, void *next)
{
	*(void **)(chunk + MEM_SIZE_FIELD + ZBX_PTR_SIZE) = next;
}

static void	**mem_ptr_to_prev_field(void *chunk)
{
	return (NULL != chunk ? (void **)(chunk + MEM_SIZE_FIELD) : NULL);
}

static void	**mem_ptr_to_next_field(void *chunk, void **first_chunk)
{
	return (NULL != chunk ? (void **)(chunk + MEM_SIZE_FIELD + ZBX_PTR_SIZE) : first_chunk);
}

/* private memory functions */

static void	*__mem_malloc(zbx_mem_info_t *info, uint32_t size)
{
	void		*chunk, *prev_chunk, *next_chunk;
	void		**next_in_prev_chunk, **prev_in_next_chunk;
	uint32_t	chunk_size;
	int		counter = 0;

	size = mem_proper_alloc_size(size);

	/* find a chunk big enough according to first-fit strategy */

	chunk = info->first_chunk;

	while (NULL != chunk && CHUNK_SIZE(chunk) < size)
	{
		counter++;
		chunk = mem_get_next_chunk(chunk);
	}

	if (NULL == chunk)
		return NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "__mem_malloc: chunk #%d is big enough", counter);

	/* gather information about previous and next chunks, and their pointers to this chunk */

	prev_chunk = mem_get_prev_chunk(chunk);
	next_chunk = mem_get_next_chunk(chunk);

	next_in_prev_chunk = mem_ptr_to_next_field(prev_chunk, &info->first_chunk);
	prev_in_next_chunk = mem_ptr_to_prev_field(next_chunk);

	/* either use the full chunk or split it */

	chunk_size = CHUNK_SIZE(chunk);

	if (chunk_size < size + 2 * MEM_SIZE_FIELD + MEM_MIN_ALLOC)
	{
		/* link previous and next chunks together */

		*next_in_prev_chunk = next_chunk;
		if (NULL != prev_in_next_chunk)
			*prev_in_next_chunk = prev_chunk;

		info->used_size += chunk_size;
		info->free_size -= chunk_size;
	}
	else
	{
		void		*new_chunk;
		uint32_t	new_chunk_size;

		/* split the chunk */

		new_chunk = chunk + MEM_SIZE_FIELD + size + MEM_SIZE_FIELD;
		new_chunk_size = chunk_size - size - 2 * MEM_SIZE_FIELD;
		mem_set_chunk_size(new_chunk, new_chunk_size);

		info->used_size += size;
		info->free_size -= chunk_size;
		info->free_size += new_chunk_size;

		chunk_size = size;

		/* link previous, new, and next chunks together */

		mem_set_prev_chunk(new_chunk, prev_chunk);
		mem_set_next_chunk(new_chunk, next_chunk);

		*next_in_prev_chunk = new_chunk;
		if (NULL != prev_in_next_chunk)
			*prev_in_next_chunk = new_chunk;
	}

	mem_set_used_chunk_size(chunk, chunk_size);

	return chunk;
}

static void	*__mem_realloc(zbx_mem_info_t *info, void *old, uint32_t size)
{
	void		*chunk, *new_chunk, *next_chunk;
	void		*prev_chunk_2, *next_chunk_2;
	void		**next_in_prev_chunk, **prev_in_next_chunk;
	uint32_t	chunk_size;
	int		next_free;

	size = mem_proper_alloc_size(size);

	chunk = old - MEM_SIZE_FIELD;
	chunk_size = CHUNK_SIZE(chunk);

	next_chunk = chunk + MEM_SIZE_FIELD + chunk_size + MEM_SIZE_FIELD;
	next_free = (next_chunk < info->hi_bound && FREE_CHUNK(next_chunk));

	if (size <= chunk_size)
	{
		/* do not reallocate if not much is freed */
		/* we are likely to want more memory again */
		if (size > chunk_size / 4)
			return chunk;

		if (next_free)
		{
			/* merge with next chunk */

			uint32_t	new_chunk_size;

			info->used_size -= chunk_size;
			info->used_size += size;
			info->free_size += chunk_size + 2 * MEM_SIZE_FIELD;
			info->free_size -= size + 2 * MEM_SIZE_FIELD;

			new_chunk = chunk + MEM_SIZE_FIELD + size + MEM_SIZE_FIELD;
			new_chunk_size = CHUNK_SIZE(next_chunk) + (chunk_size - size);
			mem_set_chunk_size(new_chunk, new_chunk_size);

			mem_set_prev_chunk(new_chunk, mem_get_prev_chunk(next_chunk));
			mem_set_next_chunk(new_chunk, mem_get_next_chunk(next_chunk));
		
			/* link previous, new, and next chunks together */

			prev_chunk_2 = mem_get_prev_chunk(new_chunk);
			next_chunk_2 = mem_get_next_chunk(new_chunk);

			next_in_prev_chunk = mem_ptr_to_next_field(prev_chunk_2, &info->first_chunk);
			prev_in_next_chunk = mem_ptr_to_prev_field(next_chunk_2);

			*next_in_prev_chunk = new_chunk;
			if (NULL != prev_in_next_chunk)
				*prev_in_next_chunk = new_chunk;
		}
		else
		{
			/* split the current one */

			uint32_t	new_chunk_size;

			info->used_size -= chunk_size;
			info->used_size += size;
			info->free_size += chunk_size;
			info->free_size -= size + 2 * MEM_SIZE_FIELD;

			new_chunk = chunk + MEM_SIZE_FIELD + size + MEM_SIZE_FIELD;
			new_chunk_size = chunk_size - size - 2 * MEM_SIZE_FIELD;
			mem_set_chunk_size(new_chunk, new_chunk_size);
			
			if (NULL == info->first_chunk)
			{
				info->first_chunk = new_chunk;
				mem_set_prev_chunk(new_chunk, NULL);
				mem_set_next_chunk(new_chunk, NULL);
			}
			else
			{
				mem_set_prev_chunk(info->first_chunk, new_chunk);
				mem_set_next_chunk(new_chunk, info->first_chunk);
				mem_set_prev_chunk(new_chunk, NULL);
				info->first_chunk = new_chunk;
			}
		}

		mem_set_used_chunk_size(chunk, size);

		return chunk;
	}

	if (next_free && chunk_size + 2 * MEM_SIZE_FIELD + CHUNK_SIZE(next_chunk) >= size)
	{
		info->used_size -= chunk_size;
		info->free_size += chunk_size;

		chunk_size += 2 * MEM_SIZE_FIELD + CHUNK_SIZE(next_chunk);

		prev_chunk_2 = mem_get_prev_chunk(next_chunk);
		next_chunk_2 = mem_get_next_chunk(next_chunk);

		next_in_prev_chunk = mem_ptr_to_next_field(prev_chunk_2, &info->first_chunk);
		prev_in_next_chunk = mem_ptr_to_prev_field(next_chunk_2);

		/* either use the full next_chunk or split it */

		if (chunk_size < size + 2 * MEM_SIZE_FIELD + MEM_MIN_ALLOC)
		{
			/* link previous and next chunks together */

			*next_in_prev_chunk = next_chunk_2;
			if (NULL != prev_in_next_chunk)
				*prev_in_next_chunk = prev_chunk_2;

			info->used_size += chunk_size;
			info->free_size -= chunk_size;
		}
		else
		{
			uint32_t	new_chunk_size;

			/* split the chunk */

			new_chunk = chunk + MEM_SIZE_FIELD + size + MEM_SIZE_FIELD;
			new_chunk_size = chunk_size - size - 2 * MEM_SIZE_FIELD;
			mem_set_chunk_size(new_chunk, new_chunk_size);

			info->used_size += size;
			info->free_size -= chunk_size;
			info->free_size += new_chunk_size;

			chunk_size = size;

			/* link previous, new, and next chunks together */

			mem_set_prev_chunk(new_chunk, prev_chunk_2);
			mem_set_next_chunk(new_chunk, next_chunk_2);

			*next_in_prev_chunk = new_chunk;
			if (NULL != prev_in_next_chunk)
				*prev_in_next_chunk = new_chunk;
		}

		mem_set_used_chunk_size(chunk, chunk_size);

		return chunk;
	}
	else if (NULL != (new_chunk = __mem_malloc(info, size)))
	{
		memcpy(new_chunk + MEM_SIZE_FIELD, chunk + MEM_SIZE_FIELD, chunk_size);

		__mem_free(info, old);

		return new_chunk;
	}
	else
	{
		void	*tmp = NULL;

		tmp = zbx_malloc(tmp, chunk_size);

		memcpy(tmp, chunk + MEM_SIZE_FIELD, chunk_size);

		__mem_free(info, old);

		new_chunk = __mem_malloc(info, size);

		if (NULL != new_chunk)
			memcpy(new_chunk + MEM_SIZE_FIELD, tmp, chunk_size);

		zbx_free(tmp);

		return new_chunk;
	}
}

static void	__mem_free(zbx_mem_info_t *info, void *ptr)
{
	void		*chunk;
	void		*prev_chunk, *next_chunk;
	void		*prev_chunk_2, *next_chunk_2;
	void		**next_in_prev_chunk, **prev_in_next_chunk;
	uint32_t	chunk_size;
	int		prev_free, next_free;

	chunk = ptr - MEM_SIZE_FIELD;
	chunk_size = CHUNK_SIZE(chunk);

	info->used_size -= chunk_size;
	info->free_size += chunk_size;

	/* see if we can merge with previous and next chunks */

	next_chunk = chunk + MEM_SIZE_FIELD + chunk_size + MEM_SIZE_FIELD;

	prev_free = (info->lo_bound < chunk && FREE_CHUNK(chunk - MEM_SIZE_FIELD));
	next_free = (next_chunk < info->hi_bound && FREE_CHUNK(next_chunk));

	if (prev_free && next_free)
	{
		info->free_size += 4 * MEM_SIZE_FIELD;

		prev_chunk = chunk - MEM_SIZE_FIELD - CHUNK_SIZE(chunk - MEM_SIZE_FIELD) - MEM_SIZE_FIELD;

		chunk_size += 4 * MEM_SIZE_FIELD + CHUNK_SIZE(prev_chunk) + CHUNK_SIZE(next_chunk);
		mem_set_chunk_size(prev_chunk, chunk_size);

		/* link previous and next chunks of prev_chunk and next_chunk */
		/* put this new chunk at the beginning of the free chunk list */

		/* prev_chunk */

		prev_chunk_2 = mem_get_prev_chunk(prev_chunk);
		next_chunk_2 = mem_get_next_chunk(prev_chunk);

		next_in_prev_chunk = mem_ptr_to_next_field(prev_chunk_2, &info->first_chunk);
		prev_in_next_chunk = mem_ptr_to_prev_field(next_chunk_2);

		*next_in_prev_chunk = next_chunk_2;
		if (NULL != prev_in_next_chunk)
			*prev_in_next_chunk = prev_chunk_2;

		/* next_chunk */

		prev_chunk_2 = mem_get_prev_chunk(next_chunk);
		next_chunk_2 = mem_get_next_chunk(next_chunk);

		next_in_prev_chunk = mem_ptr_to_next_field(prev_chunk_2, &info->first_chunk);
		prev_in_next_chunk = mem_ptr_to_prev_field(next_chunk_2);

		*next_in_prev_chunk = next_chunk_2;
		if (NULL != prev_in_next_chunk)
			*prev_in_next_chunk = prev_chunk_2;

		/* new chunk */

		chunk = prev_chunk;

		if (NULL != info->first_chunk)
			mem_set_prev_chunk(info->first_chunk, chunk);
		mem_set_prev_chunk(chunk, NULL);
		mem_set_next_chunk(chunk, info->first_chunk);
		info->first_chunk = chunk;
	}
	else if (prev_free)
	{
		info->free_size += 2 * MEM_SIZE_FIELD;

		prev_chunk = chunk - MEM_SIZE_FIELD - CHUNK_SIZE(chunk - MEM_SIZE_FIELD) - MEM_SIZE_FIELD;

		chunk_size += 2 * MEM_SIZE_FIELD + CHUNK_SIZE(prev_chunk);
		mem_set_chunk_size(prev_chunk, chunk_size);
	}
	else if (next_free)
	{
		info->free_size += 2 * MEM_SIZE_FIELD;

		chunk_size += 2 * MEM_SIZE_FIELD + CHUNK_SIZE(next_chunk);
		mem_set_chunk_size(chunk, chunk_size);
		
		mem_set_prev_chunk(chunk, mem_get_prev_chunk(next_chunk));
		mem_set_next_chunk(chunk, mem_get_next_chunk(next_chunk));
		
		/* gather information about previous and next chunks, and link */

		prev_chunk = mem_get_prev_chunk(chunk);
		next_chunk = mem_get_next_chunk(chunk);

		next_in_prev_chunk = mem_ptr_to_next_field(prev_chunk, &info->first_chunk);
		prev_in_next_chunk = mem_ptr_to_prev_field(next_chunk);

		*next_in_prev_chunk = chunk;
		if (NULL != prev_in_next_chunk)
			*prev_in_next_chunk = chunk;
	}
	else
	{
		mem_set_chunk_size(chunk, chunk_size);

		if (NULL == info->first_chunk)
		{
			info->first_chunk = chunk;
			mem_set_prev_chunk(chunk, NULL);
			mem_set_next_chunk(chunk, NULL);
		}
		else
		{
			mem_set_prev_chunk(info->first_chunk, chunk);
			mem_set_next_chunk(chunk, info->first_chunk);
			mem_set_prev_chunk(chunk, NULL);
			info->first_chunk = chunk;
		}
	}
}

/* public memory interface */

void	zbx_mem_create(zbx_mem_info_t *info, key_t shm_key, int lock_name, size_t size, const char *descr)
{
	const char	*__function_name = "zbx_mem_create";

	void		*base, *chunk_lsize, *chunk_rsize;

	descr = (NULL == descr ? "(null)" : descr);

	zabbix_log(LOG_LEVEL_DEBUG, "In %s(): descr[%s] size[%lu]",
		       __function_name, descr, size);

	/* allocate shared memory and mutex */

	if (!(MEM_MIN_SIZE <= size && size <= MEM_MAX_SIZE))
	{
		zabbix_log(LOG_LEVEL_CRIT, "Requested size [%lu] not within bounds [%d <= size <= %d].",
				size, MEM_MIN_SIZE, MEM_MAX_SIZE);
		exit(FAIL);
	}

	if (-1 == (info->shm_id = zbx_shmget(shm_key, size)))
	{
		zabbix_log(LOG_LEVEL_CRIT, "Could not allocate shared memory for %s.",
				descr);
		exit(FAIL);
	}

	if ((void *)(-1) == (base = shmat(info->shm_id, NULL, 0)))
	{
		zabbix_log(LOG_LEVEL_CRIT, "Could not attach shared memory for %s (error: [%s]).",
				descr, strerror(errno));
		exit(FAIL);
	}

	if (ZBX_NO_MUTEX != lock_name)
	{
		info->use_lock = 1;

		if (ZBX_MUTEX_ERROR == zbx_mutex_create_force(&info->mem_lock, lock_name))
		{
			zabbix_log(LOG_LEVEL_CRIT, "Could not create mutex for %s.",
					descr);
			exit(FAIL);
		}
	}
	else
		info->use_lock = 0;

	/* prepare shared memory for further allocation by creating one big chunk */

	info->orig_size = (uint32_t)size;

	chunk_rsize = ALIGN8(base + size - 8);
	if (chunk_rsize + MEM_SIZE_FIELD > base + size)
		chunk_rsize -= 8;

	chunk_lsize = ALIGN8(base);
	if (chunk_lsize - MEM_SIZE_FIELD < base)
		chunk_lsize += 8;
	chunk_lsize -= MEM_SIZE_FIELD;

	info->lo_bound = chunk_lsize;
	info->hi_bound = chunk_rsize + MEM_SIZE_FIELD;

	info->first_chunk = info->lo_bound;

	info->total_size = (uint32_t)(chunk_rsize - chunk_lsize - MEM_SIZE_FIELD);
	mem_set_chunk_size(info->first_chunk, info->total_size);

	mem_set_prev_chunk(info->first_chunk, NULL);
	mem_set_next_chunk(info->first_chunk, NULL);

	info->used_size = 0;
	info->free_size = info->total_size;

	info->mem_descr = strdup(descr);

	zabbix_log(LOG_LEVEL_DEBUG, "valid addresses: [%p, %p) total size: [%lu]",
			info->lo_bound + MEM_SIZE_FIELD,
			info->hi_bound - MEM_SIZE_FIELD,
			info->total_size);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

void	zbx_mem_destroy(zbx_mem_info_t *info)
{
	const char	*__function_name = "zbx_mem_destroy";

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (-1 == shmctl(info->shm_id, IPC_RMID, 0))
	{
		zabbix_log(LOG_LEVEL_WARNING, "Could not remove shared memory for %s (error: [%s]).",
				info->mem_descr, strerror(errno));
	}

	info->first_chunk = NULL;
	zbx_free(info->mem_descr);

	if (info->use_lock)
		zbx_mutex_destroy(&info->mem_lock);

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

void	*__zbx_mem_malloc(const char *file, int line, zbx_mem_info_t *info, const void *old, size_t size)
{
	const char	*__function_name = "zbx_mem_malloc";

	void		*chunk;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s(): size[%lu]", __function_name, size);

	if (NULL != old)
	{
		zabbix_log(LOG_LEVEL_CRIT, "[file:%s,line:%d] %s: allocating already allocated memory.",
				file, line, __function_name);
		exit(FAIL);
	}

	if (0 == size || size > MEM_MAX_SIZE)
	{
		zabbix_log(LOG_LEVEL_CRIT, "[file:%s,line:%d] %s: asking for a bad number of bytes [%lu].",
				file, line, __function_name, size);
		exit(FAIL);
	}

	LOCK_INFO;

	chunk = __mem_malloc(info, (uint32_t)size);

	UNLOCK_INFO;

	if (NULL == chunk)
	{
		zabbix_log(LOG_LEVEL_CRIT, "[file:%s,line:%d] %s: out of memory (requested %lu bytes).",
				file, line, __function_name, size);
		exit(FAIL);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return chunk + MEM_SIZE_FIELD;
}

void	*__zbx_mem_realloc(const char *file, int line, zbx_mem_info_t *info, void *old, size_t size)
{
	const char	*__function_name = "zbx_mem_realloc";

	void		*chunk;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s(): size[%lu]", __function_name, size);

	if (0 == size || size > MEM_MAX_SIZE)
	{
		zabbix_log(LOG_LEVEL_CRIT, "[file:%s,line:%d] %s: asking for a bad number of bytes [%lu].",
				file, line, __function_name, size);
		exit(FAIL);
	}

	LOCK_INFO;

	if (NULL == old)
		chunk = __mem_malloc(info, (uint32_t)size);
	else
		chunk = __mem_realloc(info, old, (uint32_t)size);

	UNLOCK_INFO;

	if (NULL == chunk)
	{
		zabbix_log(LOG_LEVEL_CRIT, "[file:%s,line:%d] %s: out of memory (requested %lu bytes).",
				file, line, __function_name, size);
		exit(FAIL);
	}

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return chunk + MEM_SIZE_FIELD;
}

void	__zbx_mem_free(const char *file, int line, zbx_mem_info_t *info, void *ptr)
{
	const char	*__function_name = "zbx_mem_free";

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (NULL == ptr)
	{
		zabbix_log(LOG_LEVEL_CRIT, "[file:%s,line:%d] %s: freeing a NULL pointer.",
				file, line, __function_name);
		exit(FAIL);
	}

	LOCK_INFO;

	__mem_free(info, ptr);

	UNLOCK_INFO;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}

void	zbx_mem_clear(zbx_mem_info_t *info)
{
	const char	*__function_name = "zbx_mem_clear";

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	LOCK_INFO;

	info->first_chunk = info->lo_bound;
	mem_set_chunk_size(info->first_chunk, info->total_size);
	mem_set_prev_chunk(info->first_chunk, NULL);
	mem_set_next_chunk(info->first_chunk, NULL);
	info->used_size = 0;
	info->free_size = info->total_size;

	UNLOCK_INFO;

	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);
}
