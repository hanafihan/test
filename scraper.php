<?php
require 'scraperwiki.php';
//hasta aqui http://www.gsmarena.com/sendo-phones-18.php
require 'scraperwiki/simple_html_dom.php';           

class GSMAParser {
    
    var $brands = array();
    var $models = 0;
    var $html;

    function init() {
        $this->brands = array();
        $this->models = 0;        


        $this->parseBrands();
        echo count( $this->brands) . ' parsed brands'. "\n";
        
        $this->parseModels();
        echo $this->models . ' parsed models'. "\n";
    }

    function parseBrands(){
        		
		$html_content = scraperwiki::scrape("http://www.gsmarena.com/makers.php3");
        $html = str_get_html($html_content);
    
        $i = 0;
        $temp = array();
		$lastb;
        foreach ($html->find("div.st-text a") as $el) {
            
            if($i % 2 == 0){
                $img = $el->find('img',0);
                $b['link'] = 'http://www.gsmarena.com/'.$el->href;
                $b['img'] = $img->src;
                $b['name'] = $img->alt;
                $temp = explode('-',$el->href);
                //$b['id'] = (int) substr($temp[2], 0, -4);
                $b['id'] = $i;
                
				//If you want to test stuff, it's best to do it with a limited set. Vivo only has 10 or so devices.
				
				if (stristr("htc samsung lg amazon asus apple motorola",$b['name'])) {
					$this->brands[] = $b;
					scraperwiki::save_sqlite(array("id"=>$b['id']), $b, "brands");
				}
				
				$lastb = $b;
            }          
        
            $i++;
 
        }
        
        $html->__destruct();
    }
    
    function parseModels(){
        $temp = array();
        foreach ($this->brands as $b) {
			echo $b['name'] ;            
            $this->parseModelsPage($b['id'],$b['name'],$b['link']);

        }

    }

    function parseModelsPage($brandId,$brandName,$page){

        $html_content = scraperwiki::scrape($page);
        $this->html = str_get_html($html_content);
		$count = 0;
		
		$array_of_ass = $this->html->find("div.makers a");
		echo " (" . sizeof($array_of_ass) . ") ";
        foreach ($array_of_ass as $el) {
            $img = $el->find('img',0);
			$m['brand'] = $brandName;
            $m['name'] = $el->find('strong',0)->innertext;
            $m['img'] = $img->src;
            $m['link'] = 'http://www.gsmarena.com/'.$el->href;
            $m['desc'] = $img->title;
            $temp = explode('-',$el->href);
            //$m['id'] = (int) substr($temp[1], 0, -4);
			$m['id'] = $this->models;
            $m['brand_id'] = $brandId;

			$count++;
			if($count == 10)
			{
				echo '|';
				$count = 0;
			}
			else
			{
				//echo '.';
			}
			
	        $html_content_single = scraperwiki::scrape($m['link']);
			$html_content_single_html = str_get_html($html_content_single);
			
			//echo strtok($html_content_single_html,"\n") . "\n";
			foreach ($html_content_single_html->find("div#specs-list tr") as $el_single) {
				if(stristr($el_single->find('a',0),'announ'))	{
					$m['announced'] = $el_single->find('td',1)->innertext;
				}

				if(stristr($el_single->find('a',0),'stat'))	{
					$m['status'] = $el_single->find('td',1)->innertext;
				}
				
				if(stristr($el_single->find('a',0),'sensor'))	{
					$sensors = $el_single->find('td',1)->innertext;
					$m['sensors'] = 1;
					if(stristr($sensors,'accel'))	{$m['accel'] = 1;} 
					else {$m['accel'] = 0;}
					if(stristr($sensors,'gyro'))	{$m['gyro'] = 1;} 
					else {$m['gyro'] = 0;}
					if(stristr($sensors,'comp'))	{$m['comp'] = 1;} 
					else {$m['comp'] = 0;}
					if(stristr($sensors,'prox'))	{$m['prox'] = 1;} 
					else {$m['prox'] = 0;}
				}
				
				if(stristr($el_single->find('a',0),'cpu'))	{
					$m['cpu'] = $el_single->find('td',1)->innertext;
				}
				
				if(stristr($el_single->find('a',0),'gpu'))	{
					$m['gpu'] = $el_single->find('td',1)->innertext;
				}
				if(stristr($el_single->find('a',0),'resol'))	{
					$m['res'] = $el_single->find('td',1)->innertext;
				}
			}
			
			$html_content_single_html->__destruct();
			
            scraperwiki::save_sqlite(array("id"=>$m['id']), $m, "models");

            $this->models++;
			
			

        }

		echo "\n";
		
        $pagination = $this->html->find("div.nav-pages",0);

        if($pagination){
           $nextPageLink = $pagination->lastChild();
           if($nextPageLink && $nextPageLink->title=="Next page"){
               $this->parseModelsPage($brandId,$brandName,'http://www.gsmarena.com/'.$nextPageLink->href);
           }
        }

        $this->html->__destruct();
      
    }

}

$parser = new GSMAParser();

$parser->init();


?>
