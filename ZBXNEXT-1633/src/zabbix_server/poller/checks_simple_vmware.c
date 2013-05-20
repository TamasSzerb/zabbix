/*
** Zabbix
** Copyright (C) 2000-2011 Zabbix SIA
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
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "common.h"
#if defined(HAVE_LIBXML2) && defined(HAVE_LIBCURL)
#include "log.h"
#include "zbxjson.h"
#include "zbxalgo.h"
#include "checks_simple_vmware.h"

#define ZBX_XPATH_LN(LN)	"//*[local-name()='" LN "']"
#define ZBX_XPATH_LN2(LN1, LN2)	ZBX_XPATH_LN(LN1) "/*[local-name()='" LN2 "']"

#define	ZBX_XML_HEADER1		"Soapaction:urn:vim25/4.1"
#define ZBX_XML_HEADER2		"Content-Type:text/xml; charset=utf-8"

typedef struct
{
	char	*data;
	size_t	allocated;
	size_t	offset;
}
ZBX_HTTPPAGE;

static ZBX_HTTPPAGE	page;

static int		vcenters_initialized = 0;
static zbx_vector_ptr_t	vcenters;

typedef struct
{
	char			*url;
	char			*username;
	char			*pwd;
	int			lastclock;
	zbx_vector_ptr_t	vcenter_vms;
}
zbx_vcenter_t;

typedef struct
{
	char			*uuid;
	char			*vmdetails;
}
zbx_vcenter_vm_t;

static size_t	WRITEFUNCTION2(void *ptr, size_t size, size_t nmemb, void *userdata)
{
	size_t	r_size = size * nmemb;

	/* first piece of data */
	if (NULL == page.data)
	{
		page.allocated = MAX(8096, r_size);
		page.offset = 0;
		page.data = malloc(page.allocated);
	}

	zbx_strncpy_alloc(&page.data, &page.allocated, &page.offset, ptr, r_size);

	return r_size;
}

static size_t	HEADERFUNCTION2(void *ptr, size_t size, size_t nmemb, void *userdata)
{
	return size * nmemb;
}

static int	vcenter_authenticate(CURL *easyhandle, char *url, char *username, char *userpwd)
{
#	define ZBX_POST_VCENTER_AUTH									\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"						\
		"<SOAP-ENV:Envelope"									\
			" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\""			\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\""			\
			" xmlns:ZSI=\"http://www.zolera.com/schemas/ZSI/\""				\
			" xmlns:soapenc=\"http://schemas.xmlsoap.org/soap/encoding/\""			\
			" xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\""			\
			" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\""				\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">"			\
			"<SOAP-ENV:Header></SOAP-ENV:Header>"						\
			"<SOAP-ENV:Body xmlns:ns1=\"urn:vim25\">"					\
				"<ns1:Login xsi:type=\"ns1:LoginRequestType\">"				\
					"<ns1:_this type=\"SessionManager\">SessionManager</ns1:_this>"	\
					"<ns1:userName>%s</ns1:userName>"				\
					"<ns1:password>%s</ns1:password>"				\
				"</ns1:Login>"								\
			"</SOAP-ENV:Body>"								\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "vcenter_authenticate";
	int		err, opt, timeout = 10;
	char		*err_str = NULL, postdata[MAX_STRING_LEN];

	zabbix_log(LOG_LEVEL_CRIT, "In %s() url:'%s' username:'%s'", __function_name, url, username);
	zabbix_log(LOG_LEVEL_CRIT, "PAGE.REQUEST: %s", ZBX_POST_VCENTER_AUTH);

	zbx_snprintf(postdata, sizeof(postdata), ZBX_POST_VCENTER_AUTH, username, userpwd);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_COOKIEFILE, "")) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_FOLLOWLOCATION, 1L)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_WRITEFUNCTION, WRITEFUNCTION2)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_HEADERFUNCTION, HEADERFUNCTION2)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_SSL_VERIFYPEER, 0L)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POST, 1L)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_URL, url)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_TIMEOUT, (long)timeout)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, postdata)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_SSL_VERIFYHOST, 0L)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: could not set cURL option [%d]: %s",
				url, opt, err_str);
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: %s", url, err_str);
		goto out;
	}
out:
	zabbix_log(LOG_LEVEL_CRIT, "PAGE.DATA: %s", page.data);
	zabbix_log(LOG_LEVEL_CRIT, "End of %s()", __function_name);

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: read_xml_value                                                   *
 *                                                                            *
 * Purpose: populate value from a xml data                                    *
 *                                                                            *
 * Parameters: data   - [IN] XML data                                         *
 *             xpath  - [IN] XML XPath                                        *
 *             values - [OUT] list of requested values                        *
 *                                                                            *
 * Return: Upon successful completion the function return SUCCEED.            *
 *         Otherwise, FAIL is returned.                                       *
 *                                                                            *
 ******************************************************************************/
static char	*read_xml_value(const char *data, char *xpath)
{
	xmlDoc		*doc;
	xmlXPathContext	*xpathCtx;
	xmlXPathObject	*xpathObj;
	xmlNodeSetPtr	nodeset;
	xmlChar		*val;
	char		*value;

	if (NULL == data)
		return NULL;

	if (NULL == (doc = xmlReadMemory(data, strlen(data), "noname.xml", NULL, 0)))
		return NULL;

	xpathCtx = xmlXPathNewContext(doc);

	if (NULL == (xpathObj = xmlXPathEvalExpression((xmlChar *)xpath, xpathCtx)))
	{
		xmlCleanupParser();
		return NULL;
	}

	if (xmlXPathNodeSetIsEmpty(xpathObj->nodesetval))
	{
		xmlXPathFreeObject(xpathObj);
		xmlCleanupParser();
		return NULL;
	}

	nodeset = xpathObj->nodesetval;

	val = xmlNodeListGetString(doc, nodeset->nodeTab[0]->xmlChildrenNode, 1);
	value = zbx_strdup(NULL, (char *)val);
	xmlFree(val);

	xmlXPathFreeObject(xpathObj);
	xmlCleanupParser();

	return value;
}

/******************************************************************************
 *                                                                            *
 * Function: read_xml_values                                                  *
 *                                                                            *
 * Purpose: populate array of values from a xml data                          *
 *                                                                            *
 * Parameters: data   - [IN] XML data                                         *
 *             xpath  - [IN] XML XPath                                        *
 *             values - [OUT] list of requested values                        *
 *                                                                            *
 * Return: Upon successful completion the function return SUCCEED.            *
 *         Otherwise, FAIL is returned.                                       *
 *                                                                            *
 ******************************************************************************/
static int	read_xml_values(const char *data, char *xpath, zbx_vector_str_t *values)
{
	const char	*__function_name = "read_xml_values";

	xmlDoc		*doc;
	xmlXPathContext	*xpathCtx;
	xmlXPathObject	*xpathObj;
	xmlNodeSetPtr	nodeset;
	xmlChar		*val;
	int		i;

	if (NULL == data)
		return FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "%s() data:'%s'", __function_name, data);

	if (NULL == (doc = xmlReadMemory(data, strlen(data), "noname.xml", NULL, 0)))
		return FAIL;

	xpathCtx = xmlXPathNewContext(doc);

	if (NULL == (xpathObj = xmlXPathEvalExpression((xmlChar *)xpath, xpathCtx)))
	{
		xmlCleanupParser();
		return FAIL;
	}

	if (xmlXPathNodeSetIsEmpty(xpathObj->nodesetval))
	{
		xmlXPathFreeObject(xpathObj);
		xmlCleanupParser();
		return FAIL;
	}

	nodeset = xpathObj->nodesetval;

	for (i = 0; i < nodeset->nodeNr; i++)
	{
		val = xmlNodeListGetString(doc, nodeset->nodeTab[i]->xmlChildrenNode, 1);
		zbx_vector_str_append(values, zbx_strdup(NULL, (char *)val));
		xmlFree(val);
	}

	xmlXPathFreeObject(xpathObj);
	xmlCleanupParser();

	return SUCCEED;
}

/******************************************************************************
 *                                                                            *
 * Function: vcenter_guestvmids_get                                           *
 *                                                                            *
 * Purpose: populate array of guest VMs                                       *
 *                                                                            *
 * Parameters: easyhandle - [IN] CURL easy handle                             *
 *             guestvmids - [OUT] list of guest VMs IDs                       *
 *                                                                            *
 * Return: Upon successful completion the function return SUCCEED.            *
 *         Otherwise, FAIL is returned.                                       *
 *                                                                            *
 * Comments: auxiliary function for vcenter_guestvmids_get()                  *
 *                                                                            *
 ******************************************************************************/
static int	vcenter_guestvmids_get(CURL *easyhandle, zbx_vector_str_t *guestvmids)
{
#	define ZBX_POST_VCENTER_VMLIST									\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"						\
		"<SOAP-ENV:Envelope"									\
		" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\""				\
		" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
		" xmlns:ZSI=\"http://www.zolera.com/schemas/ZSI/\""					\
		" xmlns:soapenc=\"http://schemas.xmlsoap.org/soap/encoding/\""				\
		" xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
		" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\""					\
		" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">"				\
		"<SOAP-ENV:Header/>"									\
		"<SOAP-ENV:Body xmlns:ns1=\"urn:vim25\">"						\
			"<ns1:RetrievePropertiesEx xsi:type=\"ns1:RetrievePropertiesExRequestType\">"	\
				"<ns1:_this type=\"PropertyCollector\">propertyCollector</ns1:_this>"	\
				"<ns1:specSet>"								\
					"<ns1:propSet>"							\
						"<ns1:type>VirtualMachine</ns1:type>"			\
						"<ns1:pathSet>config.files.vmPathName</ns1:pathSet>"	\
					"</ns1:propSet>"						\
					"<ns1:objectSet>"						\
						"<ns1:obj type=\"Folder\">group-d1</ns1:obj>"		\
						"<ns1:skip>false</ns1:skip>"				\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>visitFolders</ns1:name>"		\
							"<ns1:type>Folder</ns1:type>"			\
							"<ns1:path>childEntity</ns1:path>"		\
							"<ns1:skip>false</ns1:skip>"			\
							"<ns1:selectSet>"				\
								"<ns1:name>visitFolders</ns1:name>"	\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>dcToHf</ns1:name>"		\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>dcToVmf</ns1:name>"		\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>crToH</ns1:name>"		\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>crToRp</ns1:name>"		\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>dcToDs</ns1:name>"		\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>hToVm</ns1:name>"		\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>rpToVm</ns1:name>"		\
							"</ns1:selectSet>"				\
						"</ns1:selectSet>"					\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>dcToVmf</ns1:name>"			\
							"<ns1:type>Datacenter</ns1:type>"		\
							"<ns1:path>vmFolder</ns1:path>"			\
							"<ns1:skip>false</ns1:skip>"			\
							"<ns1:selectSet>"				\
								"<ns1:name>visitFolders</ns1:name>"	\
							"</ns1:selectSet>"				\
						"</ns1:selectSet>"					\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>dcToDs</ns1:name>"			\
							"<ns1:type>Datacenter</ns1:type>"		\
							"<ns1:path>datastore</ns1:path>"		\
							"<ns1:skip>false</ns1:skip>"			\
							"<ns1:selectSet>"				\
								"<ns1:name>visitFolders</ns1:name>"	\
							"</ns1:selectSet>"				\
						"</ns1:selectSet>"					\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>dcToHf</ns1:name>"			\
							"<ns1:type>Datacenter</ns1:type>"		\
							"<ns1:path>hostFolder</ns1:path>"		\
							"<ns1:skip>false</ns1:skip>"			\
							"<ns1:selectSet>"				\
								"<ns1:name>visitFolders</ns1:name>"	\
							"</ns1:selectSet>"				\
						"</ns1:selectSet>"					\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>crToH</ns1:name>"			\
							"<ns1:type>ComputeResource</ns1:type>"		\
							"<ns1:path>host</ns1:path>"			\
							"<ns1:skip>false</ns1:skip>"			\
						"</ns1:selectSet>"					\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>crToRp</ns1:name>"			\
							"<ns1:type>ComputeResource</ns1:type>"		\
							"<ns1:path>resourcePool</ns1:path>"		\
							"<ns1:skip>false</ns1:skip>"			\
							"<ns1:selectSet>"				\
								"<ns1:name>rpToRp</ns1:name>"		\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>rpToVm</ns1:name>"		\
							"</ns1:selectSet>"				\
						"</ns1:selectSet>"					\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>rpToRp</ns1:name>"			\
							"<ns1:type>ResourcePool</ns1:type>"		\
							"<ns1:path>resourcePool</ns1:path>"		\
							"<ns1:skip>false</ns1:skip>"			\
							"<ns1:selectSet>"				\
								"<ns1:name>rpToRp</ns1:name>"		\
							"</ns1:selectSet>"				\
							"<ns1:selectSet>"				\
								"<ns1:name>rpToVm</ns1:name>"		\
							"</ns1:selectSet>"				\
						"</ns1:selectSet>"					\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>hToVm</ns1:name>"			\
							"<ns1:type>HostSystem</ns1:type>"		\
							"<ns1:path>vm</ns1:path>"			\
							"<ns1:skip>false</ns1:skip>"			\
							"<ns1:selectSet>"				\
								"<ns1:name>visitFolders</ns1:name>"	\
							"</ns1:selectSet>"				\
						"</ns1:selectSet>"					\
						"<ns1:selectSet xsi:type=\"ns1:TraversalSpec\">"	\
							"<ns1:name>rpToVm</ns1:name>"			\
							"<ns1:type>ResourcePool</ns1:type>"		\
							"<ns1:path>vm</ns1:path>"			\
							"<ns1:skip>false</ns1:skip>"			\
						"</ns1:selectSet>"					\
					"</ns1:objectSet>"						\
				"</ns1:specSet>"							\
				"<ns1:options>"								\
				"</ns1:options>"							\
			"</ns1:RetrievePropertiesEx>"							\
		"</SOAP-ENV:Body>"									\
		"</SOAP-ENV:Envelope>\n"

	const char	*__function_name = "vcenter_guestvmids_get";

	int		err, opt, ret = FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);


	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, ZBX_POST_VCENTER_VMLIST)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "VMWare error: cannot set cURL option [%d]: %s",
				opt, curl_easy_strerror(err));
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		zabbix_log(LOG_LEVEL_WARNING, "VMWare error: %s", curl_easy_strerror(err));
		goto out;
	}

	if (SUCCEED != read_xml_values(page.data, "//*[@type='VirtualMachine']", guestvmids))
	{
		zabbix_log(LOG_LEVEL_WARNING, "VMWare error: cannot get list of vms");
		goto out;
	}

	ret = SUCCEED;
out:
	zabbix_log(LOG_LEVEL_CRIT, "End of %s():%s", __function_name, zbx_result_string(ret));

	return ret;
}

static int	vcenter_vmdata_get(CURL *easyhandle, const char *guestvmid)
{
#	define ZBX_POST_VCENTER_VMDETAILS 									\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"							\
		"<SOAP-ENV:Envelope"										\
			" xmlns:SOAP-ENC=\"http://schemas.xmlsoap.org/soap/encoding/\""				\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
			" xmlns:ZSI=\"http://www.zolera.com/schemas/ZSI/\""					\
			" xmlns:soapenc=\"http://schemas.xmlsoap.org/soap/encoding/\""				\
			" xmlns:soapenv=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
			" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\""					\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\">"				\
			"<SOAP-ENV:Header>"									\
			"</SOAP-ENV:Header>"									\
			"<SOAP-ENV:Body xmlns:ns1=\"urn:vim25\">"						\
				"<ns1:RetrieveProperties>"							\
					"<ns1:_this type=\"PropertyCollector\">propertyCollector</ns1:_this>"	\
					"<ns1:specSet>"								\
						"<ns1:propSet>"							\
							"<ns1:type>VirtualMachine</ns1:type>"			\
							"<ns1:all>false</ns1:all>"				\
							"<ns1:pathSet>summary</ns1:pathSet>"			\
						"</ns1:propSet>"						\
						"<ns1:objectSet>"						\
							"<ns1:obj type=\"VirtualMachine\">%s</ns1:obj>"		\
							"<ns1:skip>false</ns1:skip>"				\
						"</ns1:objectSet>"						\
					"</ns1:specSet>"							\
				"</ns1:RetrieveProperties>"							\
			"</SOAP-ENV:Body>"									\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "vcenter_vmdata_get";

	int		err, opt, ret = FAIL;
	char		tmp[MAX_STRING_LEN];

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() guestvmid:'%s'", __function_name, guestvmid);

	zbx_snprintf(tmp, sizeof(tmp), ZBX_POST_VCENTER_VMDETAILS, guestvmid);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, tmp)))
	{
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: cannot set cURL option [%d]: %s",
				opt, curl_easy_strerror(err));
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", curl_easy_strerror(err));
		goto out;
	}

	ret = SUCCEED;
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s():%s", __function_name, zbx_result_string(ret));

	return ret;
}

static int	vcenter_update(char *url, char *username, char *userpwd)
{
	const char		*__function_name = "vcenter_update";

	CURL			*easyhandle = NULL;
	int			opt, i, err, ret = SYSINFO_RET_FAIL;
	char			*err_str = NULL;
	zbx_vector_str_t	guestvmids;
	struct curl_slist	*headers = NULL;
	zbx_vcenter_t		*vcenter;
	zbx_vcenter_vm_t	*vcenter_vm;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() url:'%s' username:'%s' userpwd:'%s'",
			__function_name, url, username, userpwd);

	if (1 == vcenters_initialized)
	{
		ret = SYSINFO_RET_OK;
		goto out;
	}

	vcenters_initialized = 1;

	zbx_vector_str_create(&guestvmids);
	zbx_vector_ptr_create(&vcenters);

	vcenter = zbx_malloc(NULL, sizeof(zbx_vcenter_t));
	vcenter->url = zbx_strdup(NULL, url);
	vcenter->username = zbx_strdup(NULL, username);
	vcenter->pwd = zbx_strdup(NULL, userpwd);
	vcenter->lastclock = 0;
	zbx_vector_ptr_create(&vcenter->vcenter_vms);

	if (NULL == (easyhandle = curl_easy_init()))
	{
		err_str = zbx_strdup(err_str, "could not init cURL library");
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: %s", url, err_str);
		goto clean;
	}

	headers = curl_slist_append(headers, ZBX_XML_HEADER1);
	headers = curl_slist_append(headers, ZBX_XML_HEADER2);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_HTTPHEADER, headers)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: could not set cURL option [%d]: %s",
				url, opt, err_str);
		goto out;
	}

	if (SUCCEED != vcenter_authenticate(easyhandle, url, username, userpwd))
		goto clean;

	if (SUCCEED != vcenter_guestvmids_get(easyhandle, &guestvmids))
		goto clean;

	for (i = 0; i < guestvmids.values_num; i++)
	{
		if (SUCCEED != vcenter_vmdata_get(easyhandle, guestvmids.values[i]))
			goto clean;

		vcenter_vm = zbx_malloc(NULL, sizeof(zbx_vcenter_vm_t));
		vcenter_vm->uuid = read_xml_value(page.data, ZBX_XPATH_LN("uuid"));
		vcenter_vm->vmdetails = zbx_strdup(NULL, page.data);

		zbx_vector_ptr_append(&vcenter->vcenter_vms, vcenter_vm);
	}

	zbx_vector_ptr_append(&vcenters, vcenter);

	ret = SYSINFO_RET_OK;
clean:
	for (i = 0; i < guestvmids.values_num; i++)
		zbx_free(guestvmids.values[i]);
	zbx_vector_str_destroy(&guestvmids);

	curl_easy_cleanup(easyhandle);
	curl_slist_free_all(headers);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return ret;
}

static zbx_vcenter_t	*vcenter_get(const char *url, const char *username, const char *userpwd)
{
	zbx_vcenter_t	*vcenter;
	int		i;

	for (i = 0; i < vcenters.values_num; i++)
	{
		vcenter = (zbx_vcenter_t *)vcenters.values[i];

		if (0 != strcmp(vcenter->url, url))
			continue;
		if (0 != strcmp(vcenter->username, username))
			continue;
		if (0 != strcmp(vcenter->pwd, userpwd))
			continue;

		return vcenter;
	}

	return NULL;
}

static zbx_vcenter_vm_t	*vcenter_vm_get(zbx_vector_ptr_t *vcenter_vms, const char *uuid)
{
	zbx_vcenter_vm_t	*vcenter_vm;
	int			i;

	for (i = 0; i < vcenter_vms->values_num; i++)
	{
		vcenter_vm = (zbx_vcenter_vm_t *)vcenter_vms->values[i];

		if (0 != strcmp(vcenter_vm->uuid, uuid))
			continue;

		return vcenter_vm;
	}

	return NULL;
}

static int	get_ids(char *data, zbx_vector_str_t *guestvmids)
{
	xmlDoc		*doc;
	xmlNode		*root_element = NULL, *cur_node = NULL;
	xmlXPathContext	*xpathCtx;
	xmlXPathObject	*xpathObj;
	xmlChar		*val;

	if (NULL == data)
		return FAIL;

	if (NULL == (doc = xmlReadMemory(data, strlen(data), "noname.xml", NULL, 0)))
		return FAIL;

	xpathCtx = xmlXPathNewContext(doc);
	if (NULL == (xpathObj = xmlXPathEvalExpression((xmlChar *)ZBX_XPATH_LN("ManagedObjectReference") "[@type]",
			xpathCtx)))
	{
		xmlCleanupParser();
		return FAIL;
	}

	if (xmlXPathNodeSetIsEmpty(xpathObj->nodesetval))
	{
		xmlXPathFreeObject(xpathObj);
		xmlCleanupParser();
		return FAIL;
	}

	root_element = xpathObj->nodesetval->nodeTab[0];

	for (cur_node = root_element; cur_node; cur_node = cur_node->next)
	{
		if (cur_node->type == XML_ELEMENT_NODE)
		{
			if (NULL != (val = xmlNodeGetContent(cur_node)))
			{
				val = xmlNodeGetContent(cur_node);
				zbx_vector_str_append(guestvmids, zbx_strdup(NULL, (char *)val));
				xmlFree(val);
			}
		}
	}

	xmlXPathFreeObject(xpathObj);
	xmlCleanupParser();

	return SUCCEED;
}

static int	authenticate(CURL *easyhandle, char *url, char *username, char *userpwd)
{
#	define ZBX_POST_AUTH										\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"						\
		"<SOAP-ENV:Envelope"									\
			" xmlns:ns0=\"urn:vim25\""							\
			" xmlns:ns1=\"http://schemas.xmlsoap.org/soap/envelope/\""			\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\""			\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">"		\
			"<SOAP-ENV:Header/>"								\
			"<ns1:Body>"									\
				"<ns0:Login>"								\
					"<ns0:_this type=\"SessionManager\">ha-sessionmgr</ns0:_this>"	\
					"<ns0:userName>%s</ns0:userName>"				\
					"<ns0:password>%s</ns0:password>"				\
				"</ns0:Login>"								\
			"</ns1:Body>"									\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "authenticate";
	int		err, opt, timeout = 5;
	char		*err_str = NULL, postdata[MAX_STRING_LEN];

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() url:'%s' username:'%s'", __function_name, url, username);

	zbx_snprintf(postdata, sizeof(postdata), ZBX_POST_AUTH, username, userpwd);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_COOKIEFILE, "")) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_FOLLOWLOCATION, 1L)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_WRITEFUNCTION, WRITEFUNCTION2)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_HEADERFUNCTION, HEADERFUNCTION2)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_SSL_VERIFYPEER, 0L)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POST, 1L)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_URL, url)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_TIMEOUT, (long)timeout)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, postdata)) ||
			CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_SSL_VERIFYHOST, 0L)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: could not set cURL option [%d]: %s",
				url, opt, err_str);
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: %s", url, err_str);
		goto out;
	}
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return SUCCEED;
}

static int	get_guestids(CURL *easyhandle, zbx_vector_str_t *guestvmids)
{
#	define ZBX_POST_VMLIST											\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"							\
		"<SOAP-ENV:Envelope"										\
			" xmlns:ns0=\"urn:vim25\""								\
			" xmlns:ns1=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\""				\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">"			\
			"<SOAP-ENV:Header/>"									\
			"<ns1:Body>"										\
				"<ns0:RetrieveProperties>"							\
					"<ns0:_this type=\"PropertyCollector\">ha-property-collector</ns0:_this>"\
					"<ns0:specSet>"								\
						"<ns0:propSet>"							\
							"<ns0:type>HostSystem</ns0:type>"			\
							"<ns0:all>false</ns0:all>"				\
							"<ns0:pathSet>vm</ns0:pathSet>"				\
						"</ns0:propSet>"						\
						"<ns0:objectSet>"						\
							"<ns0:obj type=\"HostSystem\">ha-host</ns0:obj>"	\
						"</ns0:objectSet>"						\
					"</ns0:specSet>"							\
				"</ns0:RetrieveProperties>"							\
			"</ns1:Body>"										\
		"</SOAP-ENV:Envelope>"

	int	err, opt, ret = FAIL;
	char	*err_str = NULL;

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, ZBX_POST_VMLIST)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: cannot set cURL option [%d]: %s",
				opt, err_str);
		goto clean;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", err_str);
		goto clean;
	}

	if (SUCCEED != get_ids(page.data, guestvmids))
	{
		err_str = zbx_strdup(err_str, "unable to get list of guest ids");
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", err_str);
		goto clean;
	}

	ret = SUCCEED;
clean:
	zbx_free(err_str);

	return ret;
}

static int	get_hostdata(CURL *easyhandle)
{
#	define ZBX_POST_HOSTDETAILS										\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"							\
		"<SOAP-ENV:Envelope"										\
			" xmlns:ns0=\"urn:vim25\""								\
			" xmlns:ns1=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\""				\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">"			\
			"<SOAP-ENV:Header/>"									\
			"<ns1:Body>"										\
				"<ns0:RetrieveProperties>"							\
					"<ns0:_this type=\"PropertyCollector\">ha-property-collector</ns0:_this>"\
					"<ns0:specSet>"								\
						"<ns0:propSet>"							\
							"<ns0:type>HostSystem</ns0:type>"			\
							"<ns0:all>false</ns0:all>"				\
							"<ns0:pathSet>summary</ns0:pathSet>"			\
						"</ns0:propSet>"						\
						"<ns0:objectSet>"						\
							"<ns0:obj type=\"HostSystem\">ha-host</ns0:obj>"	\
						"</ns0:objectSet>"						\
					"</ns0:specSet>"							\
				"</ns0:RetrieveProperties>"							\
			"</ns1:Body>"										\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "get_hostdata";
	int		err, opt, ret = FAIL;
	char		*err_str = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, ZBX_POST_HOSTDETAILS)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: cannot set cURL option [%d]: %s",
				opt, err_str);
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", err_str);
		goto out;
	}

	ret = SUCCEED;
out:
	return ret;
}

static int	get_vmdata(CURL *easyhandle, const char *vmid)
{
#	define ZBX_POST_VMDETAILS 									\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"						\
		"<SOAP-ENV:Envelope "									\
			" xmlns:ns0=\"urn:vim25\" "							\
			" xmlns:ns1=\"http://schemas.xmlsoap.org/soap/envelope/\" "			\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" "			\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">"		\
			"<SOAP-ENV:Header/>"								\
			"<ns1:Body>"									\
				"<ns0:RetrieveProperties>"						\
					"<ns0:_this type=\"PropertyCollector\">ha-property-collector"	\
						"</ns0:_this>"						\
					"<ns0:specSet>"							\
						"<ns0:propSet>"						\
							"<ns0:type>VirtualMachine</ns0:type>"		\
							"<ns0:all>false</ns0:all>"			\
							"<ns0:pathSet>summary</ns0:pathSet>"		\
						"</ns0:propSet>"					\
						"<ns0:objectSet>"					\
							"<ns0:obj type=\"VirtualMachine\">%s</ns0:obj>"	\
						"</ns0:objectSet>"					\
					"</ns0:specSet>"						\
				"</ns0:RetrieveProperties>"						\
			"</ns1:Body>"									\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "get_vmdata";
	int		err, opt, ret = FAIL;
	char		*err_str = NULL, tmp[MAX_STRING_LEN];

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() vmid:'%s'", __function_name, vmid);

	zbx_snprintf(tmp, sizeof(tmp), ZBX_POST_VMDETAILS, vmid);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, tmp)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: cannot set cURL option [%d]: %s", opt, err_str);
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", err_str);
		goto out;
	}

	ret = SUCCEED;
out:
	return ret;
}

static int	get_vsphere_hoststat(char *url, char *username, char *userpwd, char *xpath, AGENT_RESULT *result)
{
	const char		*__function_name = "get_vsphere_hoststat";
	char			*value, *err_str = NULL;
	CURL			*easyhandle = NULL;
	struct curl_slist	*headers = NULL;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (NULL == url || '\0' == *url || NULL == username || '\0' == *username || NULL == userpwd)
		goto out;

	headers = curl_slist_append(headers, ZBX_XML_HEADER1);
	headers = curl_slist_append(headers, ZBX_XML_HEADER2);

	if (NULL == (easyhandle = curl_easy_init()))
	{
		err_str = zbx_strdup(err_str, "could not init cURL library");
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: %s", url, err_str);
		goto clean;
	}

	if (SUCCEED != authenticate(easyhandle, url, username, userpwd))
		goto clean;

	if (SUCCEED != get_hostdata(easyhandle))
		goto clean;

	if (NULL != (value = read_xml_value(page.data, xpath)))
	{
		SET_STR_RESULT(result, value);
		ret = SUCCEED;
	}
clean:
	curl_easy_cleanup(easyhandle);
	curl_slist_free_all(headers);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return ret;
}

static int	get_vsphere_vmstat(char *url, char *username, char *userpwd, char *uuid, char *xpath, AGENT_RESULT *result)
{
	const char		*__function_name = "get_vsphere_vmstat";
	CURL			*easyhandle = NULL;
	int			i;
	char			*uuidtmp, *value, *err_str = NULL;
	zbx_vector_str_t	guestvmids;
	struct curl_slist	*headers = NULL;
	int			ret = SYSINFO_RET_FAIL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (NULL == url || '\0' == *url || NULL == username || '\0' == *username || NULL == userpwd ||
			NULL == uuid || '\0' == *uuid)
		goto out;

	headers = curl_slist_append(headers, ZBX_XML_HEADER1);
	headers = curl_slist_append(headers, ZBX_XML_HEADER2);

	zbx_vector_str_create(&guestvmids);

	if (NULL == (easyhandle = curl_easy_init()))
	{
		err_str = zbx_strdup(err_str, "could not init cURL library");
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: %s", url, err_str);
		goto clean;
	}

	if (SUCCEED != authenticate(easyhandle, url, username, userpwd))
		goto clean;

	if (SUCCEED != get_guestids(easyhandle, &guestvmids))
		goto clean;

	for (i = 0; i < guestvmids.values_num; i++)
	{
		if (SUCCEED != get_vmdata(easyhandle, guestvmids.values[i]))
			goto clean;

		uuidtmp = read_xml_value(page.data, ZBX_XPATH_LN("uuid"));
		if (NULL == uuidtmp)
			continue;

		if (0 == strcmp(uuid, uuidtmp))
		{
			if (NULL != (value = read_xml_value(page.data, xpath)))
			{
				SET_STR_RESULT(result, value);
				ret = SYSINFO_RET_OK;
			}
			break;
		}
	}
clean:
	for (i = 0; i < guestvmids.values_num; i++)
		zbx_free(guestvmids.values[i]);
	zbx_vector_str_destroy(&guestvmids);

	curl_easy_cleanup(easyhandle);
	curl_slist_free_all(headers);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return ret;
}

static int	get_vcenter_vmstat(char *url, char *username, char *userpwd, char *uuid, char *xpath,
		AGENT_RESULT *result)
{
	const char		*__function_name = "check_vcenter_vmstat";
	int			ret = SYSINFO_RET_FAIL;
	zbx_vcenter_t		*vcenter;
	zbx_vcenter_vm_t	*vcenter_vm;
	char			*value;

	zabbix_log(LOG_LEVEL_CRIT, "In %s() uuid:'%s' xpath:'%s'", __function_name, uuid, xpath);

	if (SYSINFO_RET_OK != vcenter_update(url, username, userpwd))
	{
		zabbix_log(LOG_LEVEL_CRIT, "vcenter_update FAILED");
		goto out;
	}

	if (NULL == (vcenter = vcenter_get(url, username, userpwd)))
		goto out;

	if (NULL == (vcenter_vm = vcenter_vm_get(&vcenter->vcenter_vms, uuid)))
		goto out;

	if (NULL == (value = read_xml_value(vcenter_vm->vmdetails, xpath)))
		goto out;

	SET_STR_RESULT(result, value);
	ret = SYSINFO_RET_OK;
out:
	zabbix_log(LOG_LEVEL_CRIT, "End of %s():%d", __function_name, ret);

	return ret;
}

/******************************************************************************
 *                                                                            *
 * Function: check_vcenter_*                                                  *
 *                                                                            *
 * Purpose: vCenter related checks                                            *
 *                                                                            *
 ******************************************************************************/

int	check_vcenter_vmlist(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	struct zbx_json		j;
	int			i;
	char			*url, *username, *userpwd;
	zbx_vcenter_t		*vcenter = NULL;
	zbx_vcenter_vm_t	*vcenter_vm = NULL;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	if (NULL == (url = get_rparam(request, 0)) || '\0' == *url)
		return SYSINFO_RET_FAIL;

	if (NULL == (username = get_rparam(request, 1)) || '\0' == *username)
		return SYSINFO_RET_FAIL;

	if (NULL == (userpwd = get_rparam(request, 2)))
		return SYSINFO_RET_FAIL;

	if (SYSINFO_RET_OK != vcenter_update(url, username, userpwd))
	{
		zabbix_log(LOG_LEVEL_CRIT, "vcenter_update FAILED");
		return SYSINFO_RET_FAIL;
	}

	zbx_json_init(&j, ZBX_JSON_STAT_BUF_LEN);
	zbx_json_addarray(&j, ZBX_PROTO_TAG_DATA);

	if (NULL != (vcenter = vcenter_get(url, username, userpwd)))
	{
		for (i = 0; i < vcenter->vcenter_vms.values_num; i++)
		{
			vcenter_vm = (zbx_vcenter_vm_t *)vcenter->vcenter_vms.values[i];
			zbx_json_addobject(&j, NULL);
			zbx_json_addstring(&j, "{#UUID}", vcenter_vm->uuid, ZBX_JSON_TYPE_STRING);
			zbx_json_addstring(&j, "{#NAME}", read_xml_value(vcenter_vm->vmdetails,
					ZBX_XPATH_LN2("config", "name")), ZBX_JSON_TYPE_STRING);
			zbx_json_addstring(&j, "{#HOST}", read_xml_value(vcenter_vm->vmdetails,
					ZBX_XPATH_LN("host")), ZBX_JSON_TYPE_STRING);
			zbx_json_close(&j);
		}
	}

	zbx_json_close(&j);

	SET_STR_RESULT(result, zbx_strdup(NULL, j.buffer));

	zbx_json_free(&j);

	return SYSINFO_RET_OK;
}

int	check_vcenter_vmmemsize(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("config", "memorySizeMB"), result);
}

int	check_vcenter_vmmemsizecompressed(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("quickStats", "compressedMemory"),
			result);
}

int	check_vcenter_vmmemsizeballooned(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("quickStats", "balloonedMemory"), result);
}

int	check_vcenter_vmmemsizeswapped(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("quickStats", "swappedMemory"), result);
}

int	check_vcenter_vmstorageunshared(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("storage", "unshared"), result);
}

int	check_vcenter_vmstoragecommitted(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("storage", "committed"), result);
}

int	check_vcenter_vmstorageuncommitted(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("storage", "uncommitted"), result);
}

int	check_vcenter_vmcpunum(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("config", "numCpu"), result);
}

int	check_vcenter_vmcpuusage(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("runtime", "maxCpuUsage"), result);
}

int	check_vcenter_vmuptime(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("quickStats", "uptimeSeconds"), result);
}

int	check_vcenter_vmpowerstate(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vcenter_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("runtime", "powerState"), result);
}

/******************************************************************************
 *                                                                            *
 * Function: check_vsphere_*                                                  *
 *                                                                            *
 * Purpose: vSphere related checks                                            *
 *                                                                            *
 ******************************************************************************/

int	check_vsphere_hostcpuusage(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("quickStats", "overallCpuUsage"), result);
}

int	check_vsphere_hostfullname(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("product", "fullName"), result);
}

int	check_vsphere_hosthwcpucores(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("hardware", "numCpuCores"), result);
}

int	check_vsphere_hosthwcpufreq(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("hardware", "cpuMhz"), result);
}

int	check_vsphere_hosthwcpumodel(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("hardware", "cpuModel"), result);
}

int	check_vsphere_hosthwcputhreads(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("hardware", "numCpuThreads"), result);
}

int	check_vsphere_hosthwmemory(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("hardware", "memorySize"), result);
}

int	check_vsphere_hosthwmodel(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("hardware", "model"), result);
}

int	check_vsphere_hosthwuuid(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("hardware", "uuid"), result);
}

int	check_vsphere_hosthwvendor(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("hardware", "vendor"), result);
}

int	check_vsphere_hostmemoryused(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("quickStats", "overallMemoryUsage"), result);
}

int	check_vsphere_hoststatus(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("val", "overallStatus"), result);
}

int	check_vsphere_hostuptime(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("quickStats", "uptime"), result);
}

int	check_vsphere_hostversion(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd;

	if (3 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	return get_vsphere_hoststat(url, username, userpwd, ZBX_XPATH_LN2("product", "version"), result);
}

int	check_vsphere_vmcpunum(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("config", "numCpu"), result);
}

int	check_vsphere_vmcpuusage(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("runtime", "maxCpuUsage"), result);
}

int	check_vsphere_vmlist(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	const char		*__function_name = "check_vsphere_vmlist";
	struct zbx_json		j;
	CURL			*easyhandle = NULL;
	int			i, ret = SYSINFO_RET_FAIL;
	char			*url, *username, *userpwd, *err_str = NULL;
	zbx_vector_str_t	guestvmids;
	struct curl_slist	*headers = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s() key:'%s'", __function_name, request->key);

	if (3 != request->nparam)
		goto out;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);

	if (NULL == url || '\0' == *url || NULL == username || '\0' == *username || NULL == userpwd)
		goto out;

	headers = curl_slist_append(headers, ZBX_XML_HEADER1);
	headers = curl_slist_append(headers, ZBX_XML_HEADER2);

	zbx_vector_str_create(&guestvmids);

	if (NULL == (easyhandle = curl_easy_init()))
	{
		err_str = zbx_strdup(err_str, "could not init cURL library");
		zabbix_log(LOG_LEVEL_ERR, "VMWare URL \"%s\" error: %s", url, err_str);
		goto clean;
	}

	if (SUCCEED != authenticate(easyhandle, url, username, userpwd))
		goto clean;

	if (SUCCEED != get_guestids(easyhandle, &guestvmids))
		goto clean;

	zbx_json_init(&j, ZBX_JSON_STAT_BUF_LEN);
	zbx_json_addarray(&j, ZBX_PROTO_TAG_DATA);

	for (i = 0; i < guestvmids.values_num; i++)
	{
		if (SUCCEED != get_vmdata(easyhandle, guestvmids.values[i]))
		{
			zbx_json_free(&j);
			goto clean;
		}

		zbx_json_addobject(&j, NULL);
		zbx_json_addstring(&j, "{#UUID}", read_xml_value(page.data, ZBX_XPATH_LN("uuid")),
				ZBX_JSON_TYPE_STRING);
		zbx_json_addstring(&j, "{#NAME}", read_xml_value(page.data, ZBX_XPATH_LN2("config", "name")),
				ZBX_JSON_TYPE_STRING);
		zbx_json_close(&j);
	}

	zbx_json_close(&j);

	SET_STR_RESULT(result, strdup(j.buffer));

	ret = SYSINFO_RET_OK;

	zbx_json_free(&j);
clean:
	for (i = 0; i < guestvmids.values_num; i++)
		zbx_free(guestvmids.values[i]);
	zbx_vector_str_destroy(&guestvmids);

	curl_easy_cleanup(easyhandle);
	curl_slist_free_all(headers);
out:
	zabbix_log(LOG_LEVEL_DEBUG, "End of %s()", __function_name);

	return ret;
}

int	check_vsphere_vmmemsize(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("config", "memorySizeMB"), result);
}

int	check_vsphere_vmmemsizecompressed(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("quickStats", "compressedMemory"), result);
}

int	check_vsphere_vmmemsizeballooned(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("quickStats", "balloonedMemory"), result);
}

int	check_vsphere_vmmemsizeswapped(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("quickStats", "swappedMemory"), result);
}

int	check_vsphere_vmpowerstate(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("runtime", "powerState"), result);
}

int	check_vsphere_vmstorageunshared(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("storage", "unshared"), result);
}

int	check_vsphere_vmstoragecommitted(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("storage", "committed"), result);
}

int	check_vsphere_vmstorageuncommitted(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("storage", "uncommitted"), result);
}

int	check_vsphere_vmuptime(AGENT_REQUEST *request, AGENT_RESULT *result)
{
	char	*url, *username, *userpwd, *uuid;

	if (4 != request->nparam)
		return SYSINFO_RET_FAIL;

	url = get_rparam(request, 0);
	username = get_rparam(request, 1);
	userpwd = get_rparam(request, 2);
	uuid = get_rparam(request, 3);

	return get_vsphere_vmstat(url, username, userpwd, uuid, ZBX_XPATH_LN2("quickStats", "uptimeSeconds"), result);
}

/*static int	vcenter_get_datacenter(CURL *easyhandle)
{
#	define ZBX_POST_VCENTER_GET_DATACENTER									\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"							\
		"<SOAP-ENV:Envelope"										\
			" xmlns:ns0=\"urn:vim25\""								\
			" xmlns:ns1=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\""				\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">"			\
			"<SOAP-ENV:Header/>"									\
			"<ns1:Body>"										\
				"<ns0:RetrieveProperties>"							\
					"<ns0:_this type=\"PropertyCollector\">propertyCollector</ns0:_this>"	\
					"<ns0:specSet>"								\
						"<ns0:propSet>"							\
							"<ns0:type>Folder</ns0:type>"				\
							"<ns0:all>false</ns0:all>"				\
							"<ns0:pathSet>childEntity</ns0:pathSet>"		\
						"</ns0:propSet>"						\
						"<ns0:objectSet>"						\
							"<ns0:obj type=\"Folder\">group-d1</ns0:obj>"		\
						"</ns0:objectSet>"						\
					"</ns0:specSet>"							\
				"</ns0:RetrieveProperties>"							\
			"</ns1:Body>"										\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "vcenter_get_datacenter";
	int		err, opt, ret = FAIL;
	char		*err_str = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, ZBX_POST_VCENTER_GET_DATACENTER)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: cannot set cURL option [%d]: %s",
				opt, err_str);
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", err_str);
		goto out;
	}

	ret = SUCCEED;
out:
	return ret;
}

static int	vcenter_get_groupfolder(CURL *easyhandle)
{
#	define ZBX_POST_VCENTER_GET_GROUPFOLDER									\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"							\
		"<SOAP-ENV:Envelope"										\
			" xmlns:ns0=\"urn:vim25\""								\
			" xmlns:ns1=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\""				\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">"			\
			"<SOAP-ENV:Header/>"									\
			"<ns1:Body>"										\
				"<ns0:RetrieveProperties>"							\
					"<ns0:_this type=\"PropertyCollector\">propertyCollector</ns0:_this>"	\
					"<ns0:specSet>"								\
						"<ns0:propSet>"							\
							"<ns0:type>Datacenter</ns0:type>"			\
							"<ns0:all>false</ns0:all>"				\
							"<ns0:pathSet>hostFolder</ns0:pathSet>"			\
						"</ns0:propSet>"						\
						"<ns0:objectSet>"						\
							"<ns0:obj type=\"Datacenter\">datacenter-21</ns0:obj>"	\
						"</ns0:objectSet>"						\
					"</ns0:specSet>"							\
				"</ns0:RetrieveProperties>"							\
			"</ns1:Body>"										\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "vcenter_get_groupfolder";
	int		err, opt, ret = FAIL;
	char		*err_str = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, ZBX_POST_VCENTER_GET_GROUPFOLDER)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: cannot set cURL option [%d]: %s",
				opt, err_str);
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", err_str);
		goto out;
	}

	ret = SUCCEED;
out:
	return ret;
}

static int	vcenter_get_hostdomain(CURL *easyhandle)
{
#	define ZBX_POST_VCENTER_GET_HOSTDOMAIN									\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"							\
		"<SOAP-ENV:Envelope"										\
			" xmlns:ns0=\"urn:vim25\""								\
			" xmlns:ns1=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\""				\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">"			\
			"<SOAP-ENV:Header/>"									\
			"<ns1:Body>"										\
				"<ns0:RetrieveProperties>"							\
					"<ns0:_this type=\"PropertyCollector\">propertyCollector</ns0:_this>"	\
					"<ns0:specSet>"								\
						"<ns0:propSet>"							\
							"<ns0:type>Folder</ns0:type>"				\
							"<ns0:all>false</ns0:all>"				\
							"<ns0:pathSet>childEntity</ns0:pathSet>"		\
						"</ns0:propSet>"						\
						"<ns0:objectSet>"						\
							"<ns0:obj type=\"Folder\">group-h23</ns0:obj>"		\
						"</ns0:objectSet>"						\
					"</ns0:specSet>"							\
				"</ns0:RetrieveProperties>"							\
			"</ns1:Body>"										\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "vcenter_get_hostdomain";
	int		err, opt, ret = FAIL;
	char		*err_str = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, ZBX_POST_VCENTER_GET_HOSTDOMAIN)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: cannot set cURL option [%d]: %s",
				opt, err_str);
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", err_str);
		goto out;
	}

	ret = SUCCEED;
out:
	return ret;
}

static int	vcenter_get_hostsystem(CURL *easyhandle)
{
#	define ZBX_POST_VCENTER_GET_HOSTSYSTEM									\
		"<?xml version=\"1.0\" encoding=\"UTF-8\"?>"							\
		"<SOAP-ENV:Envelope"										\
			" xmlns:ns0=\"urn:vim25\""								\
			" xmlns:ns1=\"http://schemas.xmlsoap.org/soap/envelope/\""				\
			" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\""				\
			" xmlns:SOAP-ENV=\"http://schemas.xmlsoap.org/soap/envelope/\">"			\
			"<SOAP-ENV:Header/>"									\
			"<ns1:Body>"										\
				"<ns0:RetrieveProperties>"							\
					"<ns0:_this type=\"PropertyCollector\">propertyCollector</ns0:_this>"	\
					"<ns0:specSet>"								\
						"<ns0:propSet>"							\
							"<ns0:type>ComputeResource</ns0:type>"			\
							"<ns0:all>false</ns0:all>"				\
							"<ns0:pathSet>host</ns0:pathSet>"			\
						"</ns0:propSet>"						\
						"<ns0:objectSet>"						\
							"<ns0:obj type=\"ComputeResource\">domain-s26</ns0:obj>"\
						"</ns0:objectSet>"						\
					"</ns0:specSet>"							\
				"</ns0:RetrieveProperties>"							\
			"</ns1:Body>"										\
		"</SOAP-ENV:Envelope>"

	const char	*__function_name = "vcenter_get_hostsystem";
	int		err, opt, ret = FAIL;
	char		*err_str = NULL;

	zabbix_log(LOG_LEVEL_DEBUG, "In %s()", __function_name);

	if (CURLE_OK != (err = curl_easy_setopt(easyhandle, opt = CURLOPT_POSTFIELDS, ZBX_POST_VCENTER_GET_HOSTSYSTEM)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: cannot set cURL option [%d]: %s",
				opt, err_str);
		goto out;
	}

	memset(&page, 0, sizeof(page));

	if (CURLE_OK != (err = curl_easy_perform(easyhandle)))
	{
		err_str = zbx_strdup(err_str, curl_easy_strerror(err));
		zabbix_log(LOG_LEVEL_ERR, "VMWare error: %s", err_str);
		goto out;
	}

	ret = SUCCEED;
out:
	return ret;
}
*/
#endif
