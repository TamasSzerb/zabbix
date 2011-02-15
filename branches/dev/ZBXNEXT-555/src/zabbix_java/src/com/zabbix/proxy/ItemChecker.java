package com.zabbix.proxy;

import java.util.Vector;

import org.json.*;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

abstract class ItemChecker
{
	private static final Logger logger = LoggerFactory.getLogger(ItemChecker.class);

	public static final String JSON_TAG_CONN = "conn";
	public static final String JSON_TAG_DATA = "data";
	public static final String JSON_TAG_ERROR = "error";
	public static final String JSON_TAG_KEYS = "keys";
	public static final String JSON_TAG_PASSWORD = "password";
	public static final String JSON_TAG_PORT = "port";
	public static final String JSON_TAG_REQUEST = "request";
	public static final String JSON_TAG_RESPONSE = "response";
	public static final String JSON_TAG_USERNAME = "username";
	public static final String JSON_TAG_VALUE = "value";

	public static final String JSON_REQUEST_INTERNAL = "java proxy internal items";
	public static final String JSON_REQUEST_JMX = "java proxy jmx items";

	public static final String JSON_RESPONSE_FAILED = "failed";
	public static final String JSON_RESPONSE_SUCCESS = "success";

	protected JSONObject request;
	protected Vector<ZabbixItem> items;

	protected ItemChecker(JSONObject request) throws ZabbixException
	{
		this.request = request;

		try
		{
			JSONArray keys = request.getJSONArray(JSON_TAG_KEYS);
			items = new Vector<ZabbixItem>();

			for (int i = 0; i < keys.length(); i++)
				items.add(new ZabbixItem(keys.getString(i)));
		}
		catch (Exception e)
		{
			throw new ZabbixException(e);
		}
	}

	public JSONArray getValues() throws ZabbixException
	{
		JSONArray values = new JSONArray();

		for (ZabbixItem item : items)
			values.put(getJSONValue(item));

		return values;
	}

	protected JSONObject getJSONValue(ZabbixItem item)
	{
		JSONObject value = new JSONObject();

		try
		{
			String text = getStringValue(item);
			value.put(JSON_TAG_VALUE, text);
		}
		catch (Exception e1)
		{
			try
			{
				value.put(JSON_TAG_ERROR, e1.getMessage());
			}
			catch (JSONException e2)
			{
				Object[] logInfo = {JSON_TAG_ERROR, e1.getMessage(), e2};
				logger.warn("cannot add JSON attribute '{}' with message '{}'", logInfo);
			}
		}

		return value;
	}

	protected abstract String getStringValue(ZabbixItem item) throws Exception;
}
