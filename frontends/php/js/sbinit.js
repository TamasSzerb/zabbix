// JavaScript Document
var _PE_SB = null;

function hidegraphmenu(pe){
	if(is_null(_PE_SB)) return;
	_PE_SB.stop();
	_PE_SB = null;
	
	if((G_MENU.gmenumsover == 0 ) && (SCROLL_BAR.barmsdown == 0) && (SCROLL_BAR.arrowmsdown == 0)){
		graphsubmit();
	}
}

function showgraphmenu(obj_id){
	
	var obj = $(obj_id);
	if(!isset(obj)) return false;
	
	var pos = getPosition(obj);
	pos.top+=obj.offsetHeight+18;
	
	var scrl = $('scroll');
	scrl.style.top = pos.top+"px";
	scrl.style.left = 1+"px";
	
	G_MENU.gm_gmenu.style.top = (pos.top-108)+"px" // 110 = G_MENU height
	G_MENU.gm_gmenu.style.left = 1+"px"
	
	SCROLL_BAR.onchange = function(){
		if(is_null(_PE_SB)){
			_PE_SB = new PeriodicalExecuter(hidegraphmenu,2);
		}
	}
	
	SCROLL_BAR.barmousedown = function(){
		G_MENU.gmenuhide();
		
		if(is_null(_PE_SB)) return;
		_PE_SB.stop();
		_PE_SB = null;
	}
	
	G_MENU.gmenuload = gmenuload;
//	G_MENU.gmenumouseout = function(){G_MENU.gmenuhide(); }
	
	var gmshow = function(){
			if(SCROLL_BAR.changed == 1){
				G_MENU.gmenushow(SCROLL_BAR.period,SCROLL_BAR.getsTimeInUnix());
			}
			else{
				G_MENU.gmenushow();
			}
		}
	if(!IE){
		$('scroll_calendar').addEventListener('click',gmshow,false);
	}
	else{
		$('scroll_calendar').attachEvent('onclick',gmshow);
	}

	var date = datetoarray(G_MENU.bstime);
	
	SCROLL_BAR.tabinfoleft.innerHTML = SCROLL_BAR.FormatStampbyDHM(SCROLL_BAR.period)+" | "+date[0]+'.'+date[1]+'.'+date[2]+' '+date[3]+':'+date[4]+':'+date[5];
	
	date = datetoarray(G_MENU.bstime+SCROLL_BAR.period);
	SCROLL_BAR.tabinforight.innerHTML = date[0]+'.'+date[1]+'.'+date[2]+' '+date[3]+':'+date[4]+':'+date[5];

	
	scrl.style.visibility = 'visible';
}

function graphsubmit(){
	var scrl = $('scroll');

	scrl.style.display = 'none';
	var uri = new url(location.href);
	
	uri.setArgument('stime', SCROLL_BAR.getsTime());
	uri.setArgument('period', SCROLL_BAR.getPeriod());
	location.href = uri.getUrl();
}

function gmenuload(){
	
	var date = datetoarray(G_MENU.bstime);
	
	var stime = ''+date[2]+date[1]+date[0]+date[3]+date[4];
	var uri = new url(location.href);
	
	uri.setArgument('stime', stime);
	uri.setArgument('period', G_MENU.period);
	
	location.href = uri.getUrl();
}

function graph_zoom_init(graph_id,stime,period,width,height){
	if(!isset(graph_id) || empty(graph_id)) return;
	
	A_SBOX[graph_id].sbox = sbox_init(stime,period);
	A_SBOX[graph_id].sbox.sbox_id = graph_id;

	var igraph = $(graph_id);
	var posxy = getPosition(igraph);
	
	var boxongraph = create_box_on_obj(igraph.parentNode);
	
	boxongraph.style.top = (posxy.top+A_SBOX[graph_id].shiftT)+'px';
	boxongraph.style.left = (posxy.left+A_SBOX[graph_id].shiftL)+'px';
	
	width = width || 900;
	height = height || 200;
	
	if(empty(width)) width = 900;
	if(empty(height)) height = 900;
	
	A_SBOX[graph_id].sbox.obj.width = width;
	A_SBOX[graph_id].sbox.obj.height = height;

	boxongraph.style.height = A_SBOX[graph_id].sbox.obj.height+'px';
	boxongraph.style.width = A_SBOX[graph_id].sbox.obj.width+'px';
	
	posxy = getPosition(boxongraph);	
	
	A_SBOX[graph_id].sbox.obj.left = parseInt(posxy.left);
	A_SBOX[graph_id].sbox.obj.top = parseInt(posxy.top); 
	
	A_SBOX[graph_id].sbox.dom_obj = boxongraph;

	if(OP){
		boxongraph.addEventListener('mousedown',A_SBOX[graph_id].sbox.mousedown.bindAsEventListener(A_SBOX[graph_id].sbox),false);
		boxongraph.onmousemove = A_SBOX[graph_id].sbox.mousemove.bind(A_SBOX[graph_id].sbox);
		document.addEventListener('mouseup',A_SBOX[graph_id].sbox.mouseup.bindAsEventListener(A_SBOX[graph_id].sbox),true);
	}
	else if(!IE){
		boxongraph.addEventListener('mousedown',A_SBOX[graph_id].sbox.mousedown.bindAsEventListener(A_SBOX[graph_id].sbox),false);
		boxongraph.onmousemove = A_SBOX[graph_id].sbox.mousemove.bind(A_SBOX[graph_id].sbox);
		document.addEventListener('mouseup',A_SBOX[graph_id].sbox.mouseup.bindAsEventListener(A_SBOX[graph_id].sbox),true);
	}
	else{
//		boxongraph.attachEvent('onmousedown',A_SBOX[graph_id].sbox.mousedown.bindAsEventListener(A_SBOX[graph_id].sbox));
		igraph.attachEvent('onmousedown',A_SBOX[graph_id].sbox.mousedown.bindAsEventListener(A_SBOX[graph_id].sbox));
//		boxongraph.attachEvent('onmousemove',A_SBOX[graph_id].sbox.mousemove.bindAsEventListener(A_SBOX[graph_id].sbox));
		igraph.onmousemove = A_SBOX[graph_id].sbox.mousemove.bind(A_SBOX[graph_id].sbox);
		document.attachEvent('onmouseup',A_SBOX[graph_id].sbox.mouseup.bindAsEventListener(A_SBOX[graph_id].sbox));
	}
	
	A_SBOX[graph_id].sbox.sboxload = sboxload;
}

function sboxload(){
	var date = datetoarray(parseInt(this.stime));	// this becomes related to the object who ones that function!!
//SDI(this.sbox_id);
	var stime = ''+date[2]+date[1]+date[0]+date[3]+date[4];

	var uri = new url(location.href);
	
	uri.setArgument('stime', stime);
	uri.setArgument('period', this.period);
	
	location.href = uri.getUrl();
}

function datetoarray(unixtime){

	var date = new Date();
	date.setTime(unixtime*1000);
	
	var thedate = new Array();
	thedate[0] = date.getDate();
	thedate[1] = date.getMonth()+1;
	thedate[2] = date.getFullYear();
	thedate[3] = date.getHours();
	thedate[4] = date.getMinutes();
	thedate[5] = date.getSeconds();

	for(i = 0; i < thedate.length; i++){
		if((thedate[i]+'').length < 2) thedate[i] = '0'+thedate[i];
	}
return thedate;
}