/*
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
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

#ifndef ZABBIX_TYPES_H
#define ZABBIX_TYPES_H

#define	ZBX_FS_DBL	"%lf"
#define	ZBX_FS_DBL_EXT(p)	"%." #p "lf"

#if defined(_WINDOWS)

#ifdef _UNICODE
#	define zbx_stat(path, buf)		__zbx_stat(path, buf)
#	define zbx_open(pathname, flags)	__zbx_open(pathname, flags | O_BINARY)
#else
#	define zbx_stat(path, buf)		_stat64(path, buf)
#	define zbx_open(pathname, flags)	open(pathname, flags | O_BINARY)
#endif

#ifdef UNICODE
#	include <strsafe.h>
#	define	zbx_wsnprintf StringCchPrintf
#	define	zbx_strlen wcslen
#	define	zbx_strchr wcschr
#	define	zbx_strstr wcsstr
#	define	zbx_fullpath _wfullpath
#else
#	define	zbx_wsnprintf zbx_snprintf
#	define	zbx_strlen strlen
#	define	zbx_strchr strchr
#	define	zbx_strstr strstr
#	define	zbx_fullpath _fullpath
#endif

#ifndef __UINT64_C
#	define __UINT64_C(x)	x
#endif /* __UINT64_C */

#	define zbx_uint64_t __int64
#	define ZBX_FS_UI64 "%I64u"
#	define ZBX_FS_UO64 "%I64o"
#	define ZBX_FS_UX64 "%I64x"

#	define zbx_pid_t	int

#	define stat		_stat64
#	define snprintf		_snprintf
#	define vsnprintf	_vsnprintf

#	define alloca		_alloca

#ifndef uint32_t
#	define uint32_t	__int32
#endif /* uint32_t */

#ifndef PATH_SEPARATOR
#	define PATH_SEPARATOR	'\\'
#endif /* PATH_SEPARATOR */

#else /* _WINDOWS */

#	define zbx_stat(path, buf)		stat(path, buf)
#	define zbx_open(pathname, flags)	open(pathname, flags)

#	ifndef __UINT64_C
#		ifdef UINT64_C
#			define __UINT64_C(c) (UINT64_C(c))
#		else
#			define __UINT64_C(c) (c ## ULL)
#		endif
#	endif

#	define zbx_uint64_t uint64_t
#	if __WORDSIZE == 64
#		define ZBX_FS_UI64 "%lu"
#		define ZBX_FS_UO64 "%lo"
#		define ZBX_FS_UX64 "%lx"
#		define ZBX_OFFSET 10000000000000000UL
#	else /* __WORDSIZE == 64 */
#		ifdef HAVE_LONG_LONG_QU
#			define ZBX_FS_UI64 "%qu"
#			define ZBX_FS_UO64 "%qo"
#			define ZBX_FS_UX64 "%qx"
#		else
#			define ZBX_FS_UI64 "%llu"
#			define ZBX_FS_UO64 "%llo"
#			define ZBX_FS_UX64 "%llx"
#		endif
#		define ZBX_OFFSET 10000000000000000ULL
#	endif /* __WORDSIZE == 64 */

#	define zbx_pid_t	pid_t

#ifndef PATH_SEPARATOR
#	define PATH_SEPARATOR	'/'
#endif

#endif /* _WINDOWS */

#ifndef S_ISREG
#	define S_ISREG(x) (((x) & S_IFMT) == S_IFREG)
#endif

#ifndef S_ISDIR
#	define S_ISDIR(x) (((x) & S_IFMT) == S_IFDIR)
#endif

#define ZBX_STR2UINT64(uint,string) sscanf(string ,ZBX_FS_UI64 ,&uint);
#define ZBX_OCT2UINT64(uint,string) sscanf(string ,ZBX_FS_UO64 ,&uint);
#define ZBX_HEX2UINT64(uint,string) sscanf(string ,ZBX_FS_UX64 ,&uint);

#define ZBX_CONST_STRING(str) ""str


#endif
