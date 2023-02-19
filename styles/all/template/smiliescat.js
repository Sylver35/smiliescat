(function($){  // Avoid conflicts with other libraries
	'use strict';

	/** Display smilies Category **/
	var onCount = 0,onId = 0;
	cats.displayCats = function(id,start){
		if(onId !== id){onCount = 0;start = 0;}
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: cats.displayAjax,
			data: 'cat='+id+'&start='+onCount,
			async: true,
			success: function(result){
				onId = id;
				$('#u-open').hide();
				$('#smileys-list, #cats-list, #u-close').show();
				var listeSmilies = '',listeCats = '';
				for(var i = 0; i < result.total; i++){
					var smilie = result.list_smilies[i];
					listeSmilies += '<a onclick="insert_text(\''+smilie.code+'\',true);return false;" class="pointer" title="'+smilie.emotion+'">';
					listeSmilies += '<img src="'+result.smilies_path+smilie.src+'" alt="'+smilie.code+'" title="'+smilie.emotion+'" class="smilies" width="'+smilie.width+'" height="'+smilie.height+'"/></a> ';
				}
				for(var j = 0; j < result.nb_cats; j++){
					var category = result.categories[j];
					listeCats += '<a class="pointer tooltip '+category.css+'" onclick="cats.displayCats('+category.cat_id+',0);" style="margin:5px;" title="'+category.cat_name+'"><span>'+category.cat_name+'</span></a>('+category.cat_nb+') ';
				}
				$('#smileys-list').html(listeSmilies);
				$('#cats-list').html(listeCats);
				onId = id;
				cats.loadPagination(result.pagination);
			},
			error: function(result,statut,erreur){
				$('#smileys-list').show().html(result.responseText);
			}
		});
	}

	cats.changePage = function(thisCount){
		onCount = thisCount;
		$('#smileys-list').fadeOut(600,'linear').fadeIn(600,'linear');
		cats.displayCats(onId,onCount);
	};

	cats.loadPagination = function(total){
		$('#smileys-pagin').html('');
		var totalPages = Math.ceil(total / cats.perPage),onPage = Math.floor(onCount / cats.perPage) + 1;
		if((totalPages > 1) && (total > cats.perPage)){
			$('#div-pagin, #smileys-pagin').show();
			var items = [(onPage !== 1) ? cats.cE('span','previous'+onId,'pointer',cats.previous,cats.previous+' ',function(){cats.changePage((onPage - 2) * cats.perPage);}) : '',cats.cE('span',cats.page+'1',(onPage === 1) ? 'pagin_red' : 'pointer',cats.page+'1','1',(onPage !== 1) ? function(){cats.changePage(0);} : false)];
			var startCnt = Math.min(Math.max(1, onPage - 4),totalPages - 5),endCnt = (totalPages > 5) ? Math.max(Math.min(totalPages,onPage + 4),6) : totalPages,startFor = (totalPages > 5) ? startCnt + 1 : 2,endFor = (totalPages > 5) ? endCnt - 1 : totalPages;
			items.push((startCnt > 1 && totalPages > 5) ? ' ... ' : cats.cp());
			for(var i = startFor; i < endCnt; i++){
				items.push(cats.cE('span','nb-'+(i - 1) * cats.perPage,(i === onPage) ? 'pagin_red' : 'pointer',cats.page+i,i,(i !== onPage) ? function(){cats.changePage(this.id.replace('nb-',''));} : false));
				items.push((i < endFor) ? cats.cp() : '');
			}
			items.push((totalPages > 5) ? ((endCnt < totalPages) ? ' ... ' : cats.cp()) : '');
			items.push(cats.cE('span','nb-fin',(onPage === totalPages) ? 'pagin_red' : 'pointer',cats.page+totalPages,totalPages,(onPage !== totalPages) ? function(){cats.changePage((totalPages - 1) * cats.perPage);} : false),(onPage !== totalPages) ? cats.cE('span','next'+onId,'pointer',cats.next,' '+cats.next,function(){cats.changePage(onPage * cats.perPage);}) : cats.cE('span',false,false,false,false));
			$('#smileys-pagin').append(items);
		}else{
			$('#div-pagin, #smileys-pagin').hide();
		}
	};

	cats.cE = function(sort,id,className,title,innerHTML,onClick){
		var onElement = document.createElement(sort);
		if(id){
			onElement.id = id;
		}
		if(className){
			onElement.className = className;
		}
		if(title || title === ''){
			onElement.title = title;
		}
		if(innerHTML){
			onElement.innerHTML = innerHTML;
		}	
		if(onClick){
			onElement.onclick = onClick;
		}
		return onElement;
	};

	cats.cTN = function(e){
		return document.createTextNode(e);
	};

	cats.cp = function(){
		return cats.cE('span',false,'page-sep',false,', ',false);
	};

	cats.closeAction = function(){
		$('#smileys-list, #div-pagin, #smileys-pagin, #cats-list, #u-close').hide();
		$('#u-open').show();
	};

})(jQuery);
