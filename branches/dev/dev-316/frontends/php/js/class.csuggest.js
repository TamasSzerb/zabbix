/*
** Copyright (C) 2010 Artem "Aly" Suharev
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
// JavaScript Document
var LCL_SUGGESTS = new Array();

function createSuggest(oid){
	var sid = LCL_SUGGESTS.length;
	LCL_SUGGESTS[sid] = new CSuggest(sid, oid);

return sid;
}

var CSuggest = Class.create();
CSuggest.prototype = {
// PUBLIC
'useLocal':			true,	// use cache to find suggests
'useServer':		true,	// use server to find suggests

'saveToCache':		true,	// save results to cache

'cacheTimeOut':		60,		// cache timeout (seconds)
'suggestLimit':		15,		// suggestion show limit

'searchDelay':		200,	// milliseconds


// PRIVATE
'id':				null,	// sugg obj identity
'rpcid':			0,		// rpc request id

'needles':			{},		// searched strings
'usrNeedle':		'',		// usrNeedle
'timeoutNeedle':	null,	// Timeout reference

'cache':{
	'time':			0,		// cache creation time
	'list':			{},		// cache by word
	'needle':		{}		// cache by needle
},

'dom':{
	'input':		null,	// DOM node input
	'suggest':		null,	// DOM node suggest div
	'sugtab':		null	// DOM node suggests table
},

'hlIndex':			0,		// indicates what row should be highlilghted
'suggestCount':		0,		// suggests shown

'debug_status':		0,		// debug status: 0 - off, 1 - on, 2 - SDI;
'debug_info':		'',		// debug string
'debug_prev':		'',		// don't log repeated fnc

initialize: function(id, objid){
	this.id = id;
	this.cleanCache();

	this.dom.input = $(objid);
	addListener(this.dom.input, 'keyup', this.keyPressed.bindAsEventListener(this));
	addListener(this.dom.input, 'blur', this.hideSuggests.bindAsEventListener(this));
	addListener(window, 'resize', this.positionSuggests.bindAsEventListener(this));

	this.timeoutNeedle = null;
},

needleChange: function(e){
	this.debug('needleChange');
//--
	this.hlIndex = 0;
	this.suggestCount = 0;
	
	clearTimeout(this.timeoutNeedle);

	var target = Event.element(e);
	var needle = target.value.toLowerCase();
	
	if(empty(needle)){
		this.hideSuggests();
		return true;
	}

	this.usrNeedle = needle;
	this.needles[needle] = {'needle': needle, 'list': {}};

	var found = false;

	if(this.useLocal) found = this.searchClient(needle);

	if(!found && this.useServer){
		this.timeoutNeedle = setTimeout(this.searchServer.bind(this, needle), this.searchDelay);
	}
},

// SEARCH
searchServer: function(needle){
	this.debug('searchServer', needle);
//---
	if(needle != this.usrNeedle) return true;

	var rpcRequest = {
		'method': 'host.get',
		'params': {
			'startPattern': needle,
			'output': ['hostid', 'host'],
			'sortfield': 'host',
			'limit': this.suggestLimit
		},
		'onSuccess': this.serverRespond.bind(this, needle),
		'onFailure': function(){ throw ('Suggest Widget: search request failed.'); }
	}

	new RPC.Call(rpcRequest);

return true;
},

serverRespond: function(needle, respond){
	this.debug('serverRespond', needle);
//--

	var params = {
		'list': {},
		'needle': needle
	};

	for(var i=0; i < respond.length; i++){
		if(!isset(i, respond) || empty(respond[i])) continue;
		params.list[i] = respond[i].host.toLowerCase();
	}
	this.needles[params.needle].list = params.list;

	if(needle == this.usrNeedle){
		this.showSuggests();
		this.newSugTab(params.needle);
	}
	
	if(this.saveToCache) this.saveCache(params.needle, params.list);
},

searchClient: function(needle){
	this.debug('searchClient', needle);
//---

	var found = false;
	if(this.inCache(needle)){
		this.needles[needle].list = this.cache.needle[needle];
		found = true;
	}
	else if(!this.useServer){
		found = this.searchCache(needle);
	}

	if(found){
		this.showSuggests();
		this.newSugTab(needle);
	}

return found;
},
//-----

// Keyboard
keyPressed: function(e){
	this.debug('keyPressed');
//---

	if(!e) var e = window.event;
	var key = e.keyCode;

	switch(true){
		case(key == 27):
			this.hlIndex = 0;
			this.suggestCount = 0;
			this.highLightSuggest();
			this.hideSuggests();
			break;
		case(key==13):
			this.selectSuggest();
			break;
		case(key == 37 || key == 39 || key == 9): // left, right, tab
			break;
		case(key==38): // up
			if(this.hlIndex == 0) this.hlIndex = this.suggestCount;
			else this.hlIndex--;

			this.highLightSuggest();
			break;
		case(key==40): // down
			if(!is_null(this.dom.suggest) && (this.dom.suggest.style.display == 'none')){
				this.needleChange(e);
				break;
			}

			if(this.hlIndex == this.suggestCount) this.hlIndex = 0;
			else this.hlIndex++;

			this.highLightSuggest();
			break;
		default:
			this.needleChange(e);
	}
},


// -----------------------------------------------------------------------
// CACHE
// -----------------------------------------------------------------------
searchCache: function(needle){
	this.debug('searchCache', needle);
//---
	var fkey = needle[0];
	if(!isset(fkey, this.cache.list)) return false;

	var found = false;
	var list = {};
	for(var key in this.cache.list[fkey]){
		var value = this.cache.list[fkey][key];
		if(empty(value)) continue;

		if(key.indexOf(needle) === 0){
			list[value] = value;
			found = true;
		}
	}

	this.needles[needle].list = list;
	if(this.saveToCache) this.saveCache(needle, list);

return found;
},

inCache: function(needle){
	this.debug('inCache');
//---
	if(this.useServer){
		var dd = new Date();
		if((this.cache.time + (this.cacheTimeOut*1000)) < dd.getTime()) this.cleanCache();
	}

return isset(needle, this.cache.needle);
},

saveCache: function(needle, list){
	this.debug('saveCache');
//---
	if(this.useServer){
		var dd = new Date();
		if((this.cache.time + (this.cacheTimeOut*1000)) < dd.getTime()) this.cleanCache();
	}

// Needles
	if(!is_null(needle)) this.cache.needle[needle] = list;

// List
	for(var key in list){
		if(empty(list[key])) continue;

		var word = list[key];
		var lWord = word.toLowerCase();

		var fkey = lWord[0];

// indexing by first letter
		if(!isset(fkey, this.cache.list)) this.cache.list[fkey] = {};
		this.cache.list[fkey][lWord] = word;
	}
},

cleanCache: function(){
	this.debug('cleanCache');
//---

	var time = new Date();
	this.cache = {
		'time':		time.getTime(),
		'list':		{},
		'needle':	{}
	}
},
// -----------------------------------------------------------------------
// DOM creation
// -----------------------------------------------------------------------

showSuggests: function(){
	this.debug('showSuggests');
//---

	if(is_null(this.dom.suggest)){
		this.dom.suggest = document.createElement('div');
		this.dom.suggest = $(this.dom.suggest);

		var doc_body = document.getElementsByTagName('body')[0];
		if(empty(doc_body)) return false;

		doc_body.appendChild(this.dom.suggest);
		this.dom.suggest.className = 'suggest';

		this.positionSuggests();
	}

	this.dom.suggest.style.display = 'block';
},

hideSuggests: function(e){
	this.debug('hideSuggest');
//--

	if(!is_null(this.dom.suggest)){
		this.dom.suggest.style.display = 'none';
	}
},

positionSuggests: function(e){
	this.debug('positionSuggests');
//---

	if(is_null(this.dom.suggest)) return true;

	var pos = getPosition(this.dom.input);
	var dims = getDimensions(this.dom.input);

	this.dom.suggest.style.top = (pos.top+dims.height)+'px';
	this.dom.suggest.style.left = pos.left+'px';
},

newSugTab: function(needle){
	this.debug('newSugTab', needle);
//---
	var list = this.needles[needle].list;

	var sugTab = document.createElement('table');
	sugTab.className = 'suggest';

	var sugBody = document.createElement('tbody');
	sugTab.appendChild(sugBody);

	var count = 0;
	for(var key in list){
		if(empty(list[key])) continue;
		count++;

		var tr = document.createElement('tr');
		sugBody.appendChild(tr);
		
		tr.setAttribute('id', 'line'+count);
		tr.setAttribute('needle', list[key]);

		var td = document.createElement('td');
		tr.appendChild(td);

		td.appendChild(document.createTextNode(needle));

		var bold = document.createElement('b');
		td.appendChild(bold);
		
		bold.appendChild(document.createTextNode(list[key].substr(needle.length)));

		if(count >= this.suggestLimit) break;
	}

	if(!is_null(this.dom.sugtab)) this.dom.sugtab.remove();

	this.dom.sugtab = $(sugTab);
	this.dom.suggest.appendChild(this.dom.sugtab);

	if(count == 0) this.hideSuggests();
	this.suggestCount = count;
},

highLightSuggest: function(){
	this.debug('highLightSuggest');
//---

	var hlRows = $$('tr.highlight');
	for(var key in hlRows){
		hlRows[key].className = '';
	}

	var row = $('line'+this.hlIndex);
	if(is_null(row)) this.hlIndex = 0;

	if(this.hlIndex == 0){
		this.dom.input.value = this.usrNeedle;
	}
	else{
		row.className = 'highlight';
		this.dom.input.value = row.readAttribute('needle');
	}
},

selectSuggest: function(){
	this.debug('selectSuggest');
//---

	var needle = '';
	if(this.hlIndex == 0){
		needle = this.usrNeedle;
	}
	else{
		var row = $('line'+this.hlIndex);
		needle = row.readAttribute('needle');
	}

	this.onSelect(needle);
},

onSelect: function(selection){
	this.hideSuggests();
	//alert('You have selected "'+selection+'".\nThank You for testing Auto Suggest by Aly.');
},

debug: function(fnc_name, id){
	if(this.debug_status){
		var str = 'CSuggest['+this.id+'].'+fnc_name;
		if(typeof(id) != 'undefined') str+= ' :'+id;

//		if(this.debug_prev == str) return true;

		this.debug_info += str + '\n';
		if(this.debug_status == 2){
			SDI(str);
		}

		this.debug_prev = str;
	}
}
}