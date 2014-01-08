/*
** Zabbix
** Copyright (C) 2001-2013 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"

#if defined(_WINDOWS) && defined(_UNICODE)
int	__zbx_stat(const char *path, struct stat *buf)
{
	int	ret;
	wchar_t	*wpath;

	wpath = zbx_utf8_to_unicode(path);
	ret = _wstat64(wpath, buf);
	zbx_free(wpath);

	return ret;
}

int	__zbx_open(const char *pathname, int flags)
{
	int	ret;
	wchar_t	*wpathname;

	wpathname = zbx_utf8_to_unicode(pathname);
	ret = _wopen(wpathname, flags);
	zbx_free(wpathname);

	return ret;
}
#endif

/******************************************************************************
 *                                                                            *
 * Function: zbx_read                                                         *
 *                                                                            *
 * Purpose: Read one text line from a file descriptor into buffer             *
 *                                                                            *
 * Parameters: fd       - [IN] file descriptor to read from                   *
 *             buf      - [IN] buffer to read into                            *
 *             count    - [IN] buffer size in bytes                           *
 *             encoding - [IN] pointer a to text string describing encoding.  *
 *                        The following enocdings are recognized:             *
 *                          "UNICODE"                                         *
 *                          "UNICODEBIG"                                      *
 *                          "UNICODEFFFE"                                     *
 *                          "UNICODELITTLE"                                   *
 *                          "UTF-16"   "UTF16"                                *
 *                          "UTF-16BE" "UTF16BE"                              *
 *                          "UTF-16LE" "UTF16LE"                              *
 *                          "UTF-32"   "UTF32"                                *
 *                          "UTF-32BE" "UTF32BE"                              *
 *                          "UTF-32LE" "UTF32LE".                             *
 *                        "" (empty string) means a single-byte character set.*
 *                                                                            *
 * Return value: On success, the number of bytes read is returned (0 (zero)   *
 *               indicates end of file).                                      *
 *               On error, -1 is returned and errno is set appropriately.     *
 *                                                                            *
 * Comments: Reading stops after a newline. If the newline is read, it is     *
 *           stored into the buffer.                                          *
 *                                                                            *
 ******************************************************************************/
int	zbx_read(int fd, char *buf, size_t count, const char *encoding)
{
	size_t		i, szbyte;
	const char	*cr, *lf;
	int		nbytes;
#ifdef _WINDOWS
	__int64		offset;
#else
	off_t		offset;
#endif

	offset = lseek(fd, 0, SEEK_CUR);

	if (0 >= (nbytes = (int)read(fd, buf, count)))
		return nbytes;

	/* default is single-byte character set */
	cr = "\r";
	lf = "\n";
	szbyte = 1;

	if ('\0' != *encoding)
	{
		if (0 == strcmp(encoding, "UNICODE") || 0 == strcmp(encoding, "UNICODELITTLE") ||
				0 == strcmp(encoding, "UTF-16") || 0 == strcmp(encoding, "UTF-16LE") ||
				0 == strcmp(encoding, "UTF16") || 0 == strcmp(encoding, "UTF16LE"))
		{
			cr = "\r\0";
			lf = "\n\0";
			szbyte = 2;
		}
		else if (0 == strcmp(encoding, "UNICODEBIG") || 0 == strcmp(encoding, "UNICODEFFFE") ||
				0 == strcmp(encoding, "UTF-16BE") || 0 == strcmp(encoding, "UTF16BE"))
		{
			cr = "\0\r";
			lf = "\0\n";
			szbyte = 2;
		}
		else if (0 == strcmp(encoding, "UTF-32") || 0 == strcmp(encoding, "UTF-32LE") ||
				0 == strcmp(encoding, "UTF32") || 0 == strcmp(encoding, "UTF32LE"))
		{
			cr = "\r\0\0\0";
			lf = "\n\0\0\0";
			szbyte = 4;
		}
		else if (0 == strcmp(encoding, "UTF-32BE") || 0 == strcmp(encoding, "UTF32BE"))
		{
			cr = "\0\0\0\r";
			lf = "\0\0\0\n";
			szbyte = 4;
		}
	}

	for (i = 0; i <= (size_t)nbytes - szbyte; i += szbyte)
	{
		if (0 == memcmp(&buf[i], lf, szbyte))	/* LF (Unix) */
		{
			i += szbyte;
			break;
		}

		if (0 == memcmp(&buf[i], cr, szbyte))	/* CR (Mac) */
		{
			/* CR+LF (Windows) ? */
			if (i < (size_t)nbytes - szbyte && 0 == memcmp(&buf[i + szbyte], lf, szbyte))
				i += szbyte;

			i += szbyte;
			break;
		}
	}

	lseek(fd, offset + i, SEEK_SET);

	return (int)i;
}

int	zbx_is_regular_file(const char *path)
{
	struct stat	st;

	if (0 == zbx_stat(path, &st) && 0 != S_ISREG(st.st_mode))
		return SUCCEED;

	return FAIL;
}
