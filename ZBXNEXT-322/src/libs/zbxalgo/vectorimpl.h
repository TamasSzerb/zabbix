/*
 * vector_impl.h
 *
 *  Created on: May 10, 2013
 *      Author: wiper
 */

#ifndef VECTOR_IMPL_H_
#define VECTOR_IMPL_H_


#define	ZBX_VECTOR_ARRAY_GROWTH_FACTOR	3/2

#define	ZBX_VECTOR_IMPL(__id, __type)										\
														\
static void	__vector_ ## __id ## _ensure_free_space(zbx_vector_ ## __id ## _t *vector)			\
{														\
	if (NULL == vector->values)										\
	{													\
		vector->values_num = 0;										\
		vector->values_alloc = 32;									\
		vector->values = vector->mem_malloc_func(NULL, vector->values_alloc * sizeof(__type));		\
	}													\
	else if (vector->values_num == vector->values_alloc)							\
	{													\
		vector->values_alloc = MAX(vector->values_alloc + 1, vector->values_alloc * ZBX_VECTOR_ARRAY_GROWTH_FACTOR); \
		vector->values = vector->mem_realloc_func(vector->values, vector->values_alloc * sizeof(__type)); \
	}													\
}														\
														\
void	zbx_vector_ ## __id ## _create(zbx_vector_ ## __id ## _t *vector)					\
{														\
	zbx_vector_ ## __id ## _create_ext(vector,								\
						ZBX_DEFAULT_MEM_MALLOC_FUNC,					\
						ZBX_DEFAULT_MEM_REALLOC_FUNC,					\
						ZBX_DEFAULT_MEM_FREE_FUNC);					\
}														\
														\
void	zbx_vector_ ## __id ## _create_ext(zbx_vector_ ## __id ## _t *vector,					\
						zbx_mem_malloc_func_t mem_malloc_func,				\
						zbx_mem_realloc_func_t mem_realloc_func,			\
						zbx_mem_free_func_t mem_free_func)				\
{														\
	vector->values = NULL;											\
	vector->values_num = 0;											\
	vector->values_alloc = 0;										\
														\
	vector->mem_malloc_func = mem_malloc_func;								\
	vector->mem_realloc_func = mem_realloc_func;								\
	vector->mem_free_func = mem_free_func;									\
}														\
														\
void	zbx_vector_ ## __id ## _destroy(zbx_vector_ ## __id ## _t *vector)					\
{														\
	if (NULL != vector->values)										\
	{													\
		vector->mem_free_func(vector->values);								\
		vector->values = NULL;										\
		vector->values_num = 0;										\
		vector->values_alloc = 0;									\
	}													\
														\
	vector->mem_malloc_func = NULL;										\
	vector->mem_realloc_func = NULL;									\
	vector->mem_free_func = NULL;										\
}														\
														\
void	zbx_vector_ ## __id ## _append(zbx_vector_ ## __id ## _t *vector, __type value)				\
{														\
	__vector_ ## __id ## _ensure_free_space(vector);							\
	vector->values[vector->values_num++] = value;								\
}														\
														\
void	zbx_vector_ ## __id ## _append_ptr(zbx_vector_ ## __id ## _t *vector, __type *value)			\
{														\
	__vector_ ## __id ## _ensure_free_space(vector);							\
	vector->values[vector->values_num++] = *value;								\
}														\
														\
void	zbx_vector_ ## __id ## _remove_noorder(zbx_vector_ ## __id ## _t *vector, int index)			\
{														\
	if (!(0 <= index && index < vector->values_num))							\
	{													\
		zabbix_log(LOG_LEVEL_CRIT, "removing a non-existent element at index %d", index);		\
		exit(FAIL);											\
	}													\
														\
	vector->values[index] = vector->values[--vector->values_num];						\
}														\
														\
void	zbx_vector_ ## __id ## _remove(zbx_vector_ ## __id ## _t *vector, int index)				\
{														\
	if (!(0 <= index && index < vector->values_num))							\
	{													\
		zabbix_log(LOG_LEVEL_CRIT, "removing a non-existent element at index %d", index);		\
		exit(FAIL);											\
	}													\
														\
	vector->values_num--;											\
	memmove(&vector->values[index], &vector->values[index + 1],						\
			sizeof(__type) * (vector->values_num - index));						\
}														\
														\
void	zbx_vector_ ## __id ## _sort(zbx_vector_ ## __id ## _t *vector, zbx_compare_func_t compare_func)	\
{														\
	if (2 <= vector->values_num)										\
	{													\
		qsort(vector->values, vector->values_num, sizeof(__type), compare_func);			\
	}													\
}														\
														\
void	zbx_vector_ ## __id ## _uniq(zbx_vector_ ## __id ## _t *vector, zbx_compare_func_t compare_func)	\
{														\
	if (2 <= vector->values_num)										\
	{													\
		int	i, j = 1;										\
														\
		for (i = 1; i < vector->values_num; i++)							\
		{												\
			if (0 != compare_func(&vector->values[i - 1], &vector->values[i]))			\
				vector->values[j++] = vector->values[i];					\
		}												\
														\
		vector->values_num = j;										\
	}													\
}														\
														\
int	zbx_vector_ ## __id ## _bsearch(zbx_vector_ ## __id ## _t *vector, __type value,			\
									zbx_compare_func_t compare_func)	\
{														\
	__type	*ptr;												\
														\
	ptr = (__type *)bsearch(&value, vector->values, vector->values_num, sizeof(__type), compare_func);	\
														\
	if (NULL != ptr)											\
		return (int)(ptr - vector->values);								\
	else													\
		return FAIL;											\
}														\
														\
int	zbx_vector_ ## __id ## _lsearch(zbx_vector_ ## __id ## _t *vector, __type value, int *index,		\
									zbx_compare_func_t compare_func)	\
{														\
	while (*index < vector->values_num)									\
	{													\
		int	c = compare_func(&vector->values[*index], &value);					\
														\
		if (0 > c)											\
		{												\
			(*index)++;										\
			continue;										\
		}												\
														\
		if (0 == c)											\
			return SUCCEED;										\
														\
		if (0 < c)											\
			break;											\
	}													\
														\
	return FAIL;												\
}														\
														\
int	zbx_vector_ ## __id ## _search(zbx_vector_ ## __id ## _t *vector, __type value,				\
									zbx_compare_func_t compare_func)	\
{														\
	int	index;												\
														\
	for (index = 0; index < vector->values_num; index++)							\
	{													\
		if (0 == compare_func(&vector->values[index], &value))						\
			return index;										\
	}													\
														\
	return FAIL;												\
}														\
														\
void	zbx_vector_ ## __id ## _reserve(zbx_vector_ ## __id ## _t *vector, size_t size)				\
{														\
	if (size > vector->values_alloc)									\
	{													\
		vector->values_alloc = (int)size;								\
		vector->values = vector->mem_realloc_func(vector->values, vector->values_alloc * sizeof(__type)); \
	}													\
}														\
														\
void	zbx_vector_ ## __id ## _clear(zbx_vector_ ## __id ## _t *vector)					\
{														\
	if (NULL != vector->values)										\
	{													\
		vector->mem_free_func(vector->values);								\
		vector->values = NULL;										\
		vector->values_num = 0;										\
		vector->values_alloc = 0;									\
	}													\
}


#endif /* VECTOR_IMPL_H_ */
