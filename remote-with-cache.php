<script src="js/jquery.js"></script>
<script src="js/jquery-ui.js"></script>
<script src="js/json2.js"></script>

<meta charset="utf-8">
	<style>
	.ui-autocomplete-loading { background: white url('images/ui-anim_basic_16x16.gif') right center no-repeat; }
	.ui-autocomplete {background-color:#FFF; width:150px; border:thin solid #000;}
	.ui-corner-all:hover {background-color:#9FF; }
	</style>
    
	<script>
	$(function() 
		{
		var cache,
			lastXhr, lastTerm;
		
		$( "#birds" ).autocomplete(
			{
			minLength: 1,
			source: function( request, response ) 
				{
				var term = request.term.toLowerCase();
				
				if (term.indexOf(lastTerm) > -1)
					{
					cache[term] = [];
					for (piece in cache[lastTerm])
						{
						if (cache[lastTerm][piece].value.toLowerCase().indexOf(term) >= 0)
							cache[term].push(cache[lastTerm][piece]);							
						}
					}
					 	
				if ( term in cache ) 
					{
					response( cache[ term ] );
					return;
					}

				request = encodeURIComponent(JSON.stringify( {"term": term, "nada": "nada" } ));
				lastXhr = $.getJSON( "Controllers/ajax_list.php?request=" + request, null, function( data, status, xhr ) 
					{
					//alert ("using ajax");
					lastTerm = term;
					cache[ term ] = data;
					if ( xhr === lastXhr ) /* If the ajax response we receive isn't to the most recent sent, ignore it. */
						response( data );
					});
				}
			})	;
	});
	</script>



<div class="demo">

<div class="ui-widget">
	<label for="birds">Birds: </label>
	<input id="birds" />
</div>

</div><!-- End demo -->



<div class="demo-description">
<p>The Autocomplete widgets provides suggestions while you type into the field. Here the suggestions are bird names, displayed when at least two characters are entered into the field.</p>
<p>Similar to the remote datasource demo, though this adds some local caching to improve performance. The cache here saves just one query, and could be extended to cache multiple values, one for each term.</p>
</div><!-- End demo-description -->