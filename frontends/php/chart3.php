<? 
	include "include/config.inc.php";

#	PARAMETERS:
	
#	itemid
#	type
#	trendavg

	if(!isset($type))
	{
		$type="week";
	}

	if($type == "month")
	{
		$period=30*24*3600;
	}
	else if($type == "week")
	{
		$period=7*24*3600;
	}
	else if($type == "year")
	{
		$period=365*30*24*3600;
	}
	else
	{
		$period=7*24*3600;
		$type="week";
	}

	$sizeX=900;
	$sizeY=200;

	$shiftX=12;
	$shiftYup=13;
	$shiftYdown=7+15*2;

	$nodata=1;	


//	Header( "Content-type:  text/html"); 
	Header( "Content-type:  image/png"); 
	Header( "Expires:  Mon, 17 Aug 1998 12:51:50 GMT"); 

	$im = imagecreate($sizeX+$shiftX+61,$sizeY+$shiftYup+$shiftYdown+10); 
  
	$red=ImageColorAllocate($im,255,0,0); 
	$darkred=ImageColorAllocate($im,150,0,0); 
	$green=ImageColorAllocate($im,0,255,0); 
	$darkgreen=ImageColorAllocate($im,0,150,0); 
	$blue=ImageColorAllocate($im,0,0,255); 
	$yellow=ImageColorAllocate($im,255,255,0); 
	$cyan=ImageColorAllocate($im,0,255,255); 
	$black=ImageColorAllocate($im,0,0,0); 
	$gray=ImageColorAllocate($im,150,150,150); 
	$white=ImageColorAllocate($im,255,255,255); 

	$x=imagesx($im); 
	$y=imagesy($im);
  
	ImageFilledRectangle($im,0,0,$sizeX+$shiftX+61,$sizeY+$shiftYup+$shiftYdown+10,$white);
	ImageRectangle($im,0,0,$x-1,$y-1,$black);

	$now = time(NULL);
	$to_time=$now;
	$from_time=$to_time-14*24*3600;

	$count=array();
	$min=array();
	$max=array();
	$avg=array();

	$result=DBselect("select clock,value from history where itemid=$itemid and clock>$from_time and clock<$to_time");
	while($row=DBfetch($result))
	{
		$value=$row["value"];
		$i=intval( 900*(($row["clock"]+3*3600)%(24*3600))/(24*3600));
//		$value=$i;
//		if($i==0) echo "B:",date("dS of F Y h:i:s A",$row["clock"]),"<br>";
//		if($i==899) echo "E:",date("dS of F Y h:i:s A",$row["clock"]),"<br>";

		if( (!isset($max[$i])) || ($max[$i]<$value))
		{
			$max[$i]=$value;
		}
		if(!isset($min[$i]) || ($min[$i]>$value))	$min[$i]=$value;
		if(isset($count[$i]))
		{
			$count[$i]++;
		}
		else
		{
			$count[$i]=1;
		};
		if(isset($avg[$i]))
		{
			$avg[$i]=($value+($count[$i]-1)*$avg[$i])/$count[$i];
		}
		else
		{
			$avg[$i]=$value;
		}
		$nodata=0;
	}

	$count_now=array();
	$avg_now=array();
	$to_time=$now;
	$from_time=$to_time-$period;
	$result=DBselect("select clock,value from history where itemid=$itemid and clock>$from_time and clock<$to_time");
	while($row=DBfetch($result))
	{
		$value=$row["value"];
		$i=intval( 900*(($row["clock"]+3*3600)%(24*3600))/(24*3600));
//		$i=intval( 900*(($to_time-$row["clock"]+75600)%(24*3600))/(24*3600));
//		echo (mktime(0, 0, 0, 07, 27,2002)-75600)%(24*3600),"<br>";

		if(isset($count_now[$i]))
		{
			$count_now[$i]++;
		}
		else
		{
			$count_now[$i]=1;
		};
		if(isset($avg_now[$i]))
		{
			$avg_now[$i]=($value+($count_now[$i]-1)*$avg_now[$i])/$count_now[$i];
		}
		else
		{
			$avg_now[$i]=$value;
		}
	}

	for($i=0;$i<=$sizeY;$i+=$sizeY/5)
	{
		ImageDashedLine($im,$shiftX,$i+$shiftYup,$sizeX+$shiftX,$i+$shiftYup,$gray);
	}

	for($i=0;$i<=$sizeX;$i+=$sizeX/24)
	{
		ImageDashedLine($im,$i+$shiftX,$shiftYup,$i+$shiftX,$sizeY+$shiftYup,$gray);
		if($nodata == 0)
		{
			ImageString($im, 1,$i+$shiftX-11, $sizeY+$shiftYup+5, date("H:i",-3*3600+24*3600*$i/900) , $black);
		}
	}

	unset($maxY);
	unset($minY);

	if($nodata == 0)
	{
		$maxY=max($avg);
		$minY=min($avg);
	}

	$maxX=900;
	$minX=0;

	if(isset($minY)&&($maxY)&&($minX!=$maxX)&&($minY!=$maxY))
	{
		for($i=0;$i<900;$i++)
		{
			if($count[$i]>0)
			{
/*				if(!isset($trendavg))
				{
					$x1=$sizeX*($i-$minX)/($maxX-$minX);
					$y1=$sizeY*($max[$i]-$minY)/($maxY-$minY);
					$x2=$x1;
					$y2=0;
					$y1=$sizeY-$y1;
					$y2=$sizeY-$y2;

					ImageLine($im,$x1+$shiftX,$y1+$shiftYup,$x2+$shiftX,$y2+$shiftYup,$blue);
				}*/

				$x1=$sizeX*($i-$minX)/($maxX-$minX);
				$y1=$sizeY*($avg[$i]-$minY)/($maxY-$minY);
				$x2=$x1;
				$y2=0;
				$y1=$sizeY-$y1;
				$y2=$sizeY-$y2;
	
				ImageLine($im,$x1+$shiftX,$y1+$shiftYup,$x2+$shiftX,$y2+$shiftYup,$darkgreen);

/*				if(!isset($trendavg))
				{
					$x1=$sizeX*($i-$minX)/($maxX-$minX);
					$y1=$sizeY*($min[$i]-$minY)/($maxY-$minY);
					$x2=$x1;
					$y2=0;
					$y1=$sizeY-$y1;
					$y2=$sizeY-$y2;
	
					ImageLine($im,$x1+$shiftX,$y1+$shiftYup,$x2+$shiftX,$y2+$shiftYup,$green);
				}*/
			}
			if(($count_now[$i]>0)&&($count_now[$i-1]>0))
			{
				if($i>0)
				{
					$x1=$sizeX*($i-$minX)/($maxX-$minX);
					$y1=$sizeY*($avg_now[$i]-$minY)/($maxY-$minY);
					$x2=$sizeX*($i-$minX-1)/($maxX-$minX);
					$y2=$sizeY*($avg_now[$i-1]-$minY)/($maxY-$minY);
//					$x2=$x1;
//					$y2=0;
					$y1=$sizeY-$y1;
					$y2=$sizeY-$y2;
	
					ImageLine($im,$x1+$shiftX,$y1+$shiftYup,$x2+$shiftX,$y2+$shiftYup,$darkred);
				}
			}


#			ImageStringUp($im, 1, $x1+10, $sizeY+$shiftYup+15, $i , $red);
		}
	}
	else
	{
//		ImageLine($im,$shiftX,$shiftYup+$sizeY/2,$sizeX+$shiftX,$shiftYup+$sizeY/2,$green);
	}

	if($nodata == 0)
	{
		for($i=0;$i<=$sizeY;$i+=$sizeY/5)
		{
			ImageString($im, 1, $sizeX+5+$shiftX, $sizeY-$i-4+$shiftYup, $i*($maxY-$minY)/$sizeY+$minY , $darkred);
		}

//		date("dS of F Y h:i:s A",DBget_field($result,0,0));

//		ImageString($im, 1,10,                $sizeY+$shiftY+5, date("dS of F Y h:i:s A",$minX) , $red);
//		ImageString($im, 1,$sizeX+$shiftX-168,$sizeY+$shiftY+5, date("dS of F Y h:i:s A",$maxX) , $red);
	}
	else
	{
		ImageString($im, 2,$sizeX/2 -50,                $sizeY+$shiftYup+3, "NO DATA FOR THIS PERIOD" , $red);
	}

	ImageFilledRectangle($im,$shiftX,$sizeY+$shiftYup+19+15*0,$shiftX+5,$sizeY+$shiftYup+15+9+15*0,$darkgreen);
	ImageRectangle($im,$shiftX,$sizeY+$shiftYup+19+15*0,$shiftX+5,$sizeY+$shiftYup+15+9+15*0,$black);
	if($type=="year")
	{
		ImageString($im, 2,$shiftX+9,$sizeY+$shiftYup+15*0+15, "Average for last 365 days", $black);
	}
	else if($type=="month")
	{
		ImageString($im, 2,$shiftX+9,$sizeY+$shiftYup+15*0+15, "Average for last 30 days", $black);
	}
	else
	{
		ImageString($im, 2,$shiftX+9,$sizeY+$shiftYup+15*0+15, "Average for last 7 days", $black);
	}

	ImageFilledRectangle($im,$shiftX,$sizeY+$shiftYup+19+15*1,$shiftX+5,$sizeY+$shiftYup+15+9+15*1,$darkred);
	ImageRectangle($im,$shiftX,$sizeY+$shiftYup+19+15*1,$shiftX+5,$sizeY+$shiftYup+15+9+15*1,$black);
	ImageString($im, 2,$shiftX+9,$sizeY+$shiftYup+15*1+15, "Average today", $black);

//	ImageString($im, 1,$shiftX, $sizeY+$shiftY+15, "AVG (LAST WEEK)" , $darkgreen);
//	ImageString($im, 1,$shiftX+80, $sizeY+$shiftY+15, "AVG (TODAY)" , $darkred);

	ImageStringUp($im,0,2*$shiftX+$sizeX+40,$sizeY+$shiftYup+$shiftYdown, "http://zabbix.sourceforge.net", $gray);

	ImagePng($im); 
	ImageDestroy($im); 
?>
