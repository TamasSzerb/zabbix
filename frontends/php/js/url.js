// JavaScript Document
var url = Class.create();

url.prototype = {
url: 		'',		//	actually, it's depricated/private variable 
port:		 -1,
host: 		'',
protocol: 	'',
username:	'',
password:	'',
filr:		'',
reference:	'',
path:		'',
query:		'',
arguments: new Array(),

initialize: function(url){
	this.url=unescape(url);
	
	this.query=(this.url.indexOf('?')>=0)?this.url.substring(this.url.indexOf('?')+1):'';
	if(this.query.indexOf('#')>=0) this.query=this.query.substring(0,this.query.indexOf('#'));
	
	var protocolSepIndex=this.url.indexOf('://');
	if(protocolSepIndex>=0){
		this.protocol=this.url.substring(0,protocolSepIndex).toLowerCase();
		this.host=this.url.substring(protocolSepIndex+3);
		if(this.host.indexOf('/')>=0) this.host=this.host.substring(0,this.host.indexOf('/'));
		var atIndex=this.host.indexOf('@');
		if(atIndex>=0){
			var credentials=this.host.substring(0,atIndex);
			var colonIndex=credentials.indexOf(':');
			if(colonIndex>=0){
				this.username=credentials.substring(0,colonIndex);
				this.password=credentials.substring(colonIndex);
			}else{
				this.username=credentials;
			}
			this.host=this.host.substring(atIndex+1);
		}
		var portColonIndex=this.host.indexOf(':');
		if(portColonIndex>=0){
			this.port=this.host.substring(portColonIndex);
			this.host=this.host.substring(0,portColonIndex);
		}
		this.file=this.url.substring(protocolSepIndex+3);
		this.file=this.file.substring(this.file.indexOf('/'));
	}else{
		this.file=this.url;
	}
	if(this.file.indexOf('?')>=0) this.file=this.file.substring(0, this.file.indexOf('?'));

	var refSepIndex=url.indexOf('#');
	if(refSepIndex>=0){
		this.file=this.file.substring(0,refSepIndex);
		this.reference=this.url.substring(this.url.indexOf('#'));
	}
	this.path=this.file;
	if(this.query.length>0) this.file+='?'+this.query;
	if(this.reference.length>0) this.file+='#'+this.reference;
	
	this.getArguments();
},

getArguments: function(){
	var args=this.query.split('&');
	var keyval='';
	
	if(args.length<1) return;
	
	for(i=0;i<args.length;i++){
		keyval=args[i].split('=');
		this.arguments[i] = new Array(keyval[0],(keyval.length==1)?keyval[0]:keyval[1]);
	}
},

getArgumentValue: function(key){
	if(key.length<1) return '';
	for(i=0; i < this.arguments.length; i++){
		if(this.arguments[i][0] == key) return this.arguments[i][1];
	}
	
return '';
},

getArgumentValues: function(){
	var a=new Array();
	var b=this.query.split('&');
	var c='';
	if(b.length<1) return a;
	for(i=0;i<b.length;i++){
		c=b[i].split('=');
		a[i]=new Array(c[0],((c.length==1)?c[0]:c[1]));
	}
return a;
},

getUrl: function(){
	var uri = (this.protocol.length > 0)?(this.protocol+'://'):'';
	uri +=  (this.username.length > 0)?(this.username):'';
	uri +=  (this.password.length > 0)?(':'+this.password):'';
	uri +=  (this.host.length > 0)?(this.host):'';
	uri +=  (this.path.length > 0)?(this.path):'';
	uri +=  (this.query.length > 0)?('?'+this.query):'';
	uri +=  (this.reference.length > 0)?('#'+this.reference):'';
return encodeURI(uri);
},

setArgument: function(key,value){

	var valueisset = false;
	if(typeof(key) == 'undefined') throw 'Invalid argument past for setArgument';
	value = value || '';

	for(i=0; i < this.arguments.length; i++){
		if(this.arguments[i][0] == key){
			valueisset = true;
			this.arguments[i][1] = value;
		};
	}	
	if(!valueisset)	this.arguments[this.arguments.length] = new Array(key,value);
	this.formatQuery();
},

formatQuery: function(){
	if(this.arguments.lenght < 1) return;
	
	var query = '';
	for(i=0; i < this.arguments.length; i++){		
		query+=this.arguments[i][0]+'='+this.arguments[i][1]+'&';
	}
	this.query = query.substring(0,query.length-1);
},

getPort: function(){ 
	return this.port;
},

setPort: function(port){
	this.port = port;
},

getQuery: function(){ 
	return this.query;
},

setQuery: function(query){ 
	this.query = query;
	this.getArgumentValues();
	this.formatQuery();
},

/* Returns the protocol of this URL, i.e. 'http' in the url 'http://server/' */
getProtocol: function(){
	return this.protocol;
},

setProtocol: function(protocol){
	this.protocol = protocol;
},
/* Returns the host name of this URL, i.e. 'server.com' in the url 'http://server.com/' */
getHost: function(){
	return this.host;
},

setHost: function(set){
	this.host = host;
},

/* Returns the user name part of this URL, i.e. 'joe' in the url 'http://joe@server.com/' */
getUserName: function(){
	return this.username;
},

setUserName: function(username){
	this.username = username;
},

/* Returns the password part of this url, i.e. 'secret' in the url 'http://joe:secret@server.com/' */
getPassword: function(){
	return this.password;
},

setPassword: function(password){
	this.password = password;
},

/* Returns the file part of this url, i.e. everything after the host name. */
getFile: function(){
	return this.file = file;
},

setFile: function(file){
	this.file = file;
},

/* Returns the reference of this url, i.e. 'bookmark' in the url 'http://server/file.html#bookmark' */
getReference: function(){
	return this.reference;
},

setReference: function(reference){
	this.reference = reference;
},

/* Returns the file path of this url, i.e. '/dir/file.html' in the url 'http://server/dir/file.html' */
getPath: function(){
	return this.path;
},

setPath: function(path){
	this.path = path;
}

}