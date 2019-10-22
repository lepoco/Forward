<?php
/**
 * @package Forward
 *
 * @author RapidDev
 * @copyright Copyright (c) 2019, RapidDev
 * @link https://www.rdev.cc/forward
 * @license https://opensource.org/licenses/MIT
 */
	namespace Forward;
	defined('ABSPATH') or die('No script kiddies please!');
	
	$this->head(); $this->menu();

	$records = $this->DB['records']->select('__id,__created_at,url,clicks,stats,referrers,locations')->orderBy('__created_at', 'DESC')->results();
	$siteurl = $this->DB['options']->get('siteurl')->value;

	$total_clicks = 0;

	$locations = array();
	$referrers = array();

	$date = array(
		'y' => date('Y', time()),
		'm' => date('m', time()),
		'd' => date('d', time())
	);

	$date['days'] = cal_days_in_month(CAL_GREGORIAN, (int)$date['m'], (int)$date['y']);

	foreach ($records as $key => $record)
	{
		$total_clicks += $record['clicks'];

		if(is_array($record['locations']))
		{
			foreach ($record['locations'] as $location => $count)
			{
				if(array_key_exists($location, $locations))
				{
					$locations[$location] += $count;
				}
				else
				{
					$locations[$location] = $count;
				}
			}
		}

		if(is_array($record['referrers']))
		{
			foreach ($record['referrers'] as $referrer => $count)
			{
				if(array_key_exists($referrer, $referrers))
				{
					$referrers[$referrer] += $count;
				}
				else
				{
					$referrers[$referrer] = $count;
				}
			}
		}

		$stats = '';
		if(is_array($record['stats']) && isset($record['stats'][$date['y'].'-'.$date['m']]))
		{
			for ($i=1; $i <= $date['days']; $i++)
			{ 
				$stats .= ($i > 1 ? '/' : '').(isset($record['stats'][$date['y'].'-'.$date['m']][$i]) ? $record['stats'][$date['y'].'-'.$date['m']][$i] : '0');
			}
		}
		else
		{
			for($i=1; $i <= $date['days']; $i++)
			{
				$stats .= ($i > 1 ? '/': '').'0';
			}
		}

		$records[$key]['stats'] = $stats;
	}

	if(count($locations) == 0)
		$locations = array('unknown' => 0);
	arsort($locations);

	if(count($referrers) == 0)
		$referrers = array('unknown' => 0);
	arsort($referrers);

	switch (key($locations)) {
		case 'en-us':
			$top_lang = 'English';
			break;
		
		default:
			$top_lang = key($locations);
			break;
	}

	if(key($referrers) == 'direct')
		$top_referrer = 'Email, SMS, Direct';
	else
		$top_referrer = key($referrers);

?>

<div id="red-dashboard" class="block-page distance-navbar">
	<div class="container-fluid">
		<div class="row">
			<div class="col-12 col-lg-3 col-no-gutters" id="records_list">
				<div class="card links-card"><div class="card-body"><small><strong id="total_records_count"><?php echo count($records); ?></strong> total links</small></div></div>
				<?php $c = 0;foreach ($records as $key => $record):$c++;
					$preURL = str_replace(array('https://www.', 'https://'), array('', ''), $record['url']);
					if(strlen($preURL) > 35)
						$preURL = substr($preURL, 0, 35).'...';
					?>
					<div class="card links-card"<?php echo ($c == 1 ? ' id="first-record"':''); ?> data-daily="<?php echo $record['stats']; ?>" data-date="<?php echo date('Y-m-d H:i', $record['__created_at']); ?>" data-url="<?php echo $record['url']; ?>" data-slug="<?php echo $record['__id']; ?>" data-clicks="<?php echo $record['clicks']; ?>">
						<div class="card-body">
							<div>
								<small><?php echo date('Y-m-d', $record['__created_at']); ?></small>
								<h2><a target="_blank" rel="noopener" href="<?php echo $siteurl.$record['__id']; ?>">/<?php echo $record['__id']; ?></a></h2>
								<p><a target="_blank" rel="noopener" href="<?php echo $record['url'] ?>"><?php echo $preURL; ?></a></p>
							</div>
							<span><?php echo $record['clicks']; ?></span>
						</div>
					</div>
				<?php endforeach ?>
			</div>
			<div class="col-12 col-lg-9" style="padding-top:32px;padding-bottom:15px;height: inherit;overflow: auto;">
				<div class="container-fluid">
					<div class="row">
						<div class="col-12">
							<div id="add-alert" class="alert alert-danger fade show" role="alert" style="display: none;">
								<strong>Holy guacamole!</strong> <span id="error_text">Something went wrong!</span>
							</div>
							<div id="add-success" class="alert alert-success fade show" role="alert" style="display: none;">
								<strong>Success!</strong> New link was added.
							</div>
							<form id="add-record-form" action="<?php echo $this->home_url().'dashboard/ajax/'; ?>">
								<?php
								//Find unical slug
								$rand = RED::rand(6);
								$sucRand = true;
								while ($sucRand) {
									$slug = $this->DB['records']->select('__id,url')->where('__id','=',$rand)->results();
									if(isset($slug[0]))
										$rand = RED::rand(6);
									else
										$sucRand = false;
								}
								?>
								<input type="hidden" value="addRecord" name="action">
								<input type="hidden" value="<?php echo RED::encrypt('ajax_add_record_nonce', 'nonce'); ?>" name="nonce">
								<input type="hidden" value="<?php echo $rand; ?>" id="randValue" name="randValue">
								<div class="row">
									<div class="col-4 col-lg-3">
										<div class="form-group">
											<input type="text" id="forward-url" name="forward-url" class="form-control" placeholder="https://">
										</div>
									</div>
									<div class="col-4 col-lg-3 col-no-gutters">
										<div class="form-group">
											<input type="text" id="forward-slug" name="forward-slug" class="form-control" placeholder="<?php echo $rand; ?>">
										</div>
									</div>
									<div class="col-4 col-lg-3">
										<button type="submit" id="add-record-send" class="btn btn-block btn-outline-dark">Add new</button>
									</div>
								</div>
							</form>
						</div>
						<div class="col-12" style="margin-top:32px;">
							<ul class="list-inline">
								<li class="list-inline-item">
									<div class="stats-header">
										<svg viewBox="0 0 24 24"><path d="M6,16.5L3,19.44V11H6M11,14.66L9.43,13.32L8,14.64V7H11M16,13L13,16V3H16M18.81,12.81L17,11H22V16L20.21,14.21L13,21.36L9.53,18.34L5.75,22H3L9.47,15.66L13,18.64" /></svg>
										<div>
											<h1><?php echo $total_clicks; ?></h1>
											<p>Total clicks</p>
										</div>
									</div>
								</li>
								<li class="list-inline-item">
									<div class="stats-header">
										<svg viewBox="0 0 24 24"><path d="M10.6 13.4A1 1 0 0 1 9.2 14.8A4.8 4.8 0 0 1 9.2 7.8L12.7 4.2A5.1 5.1 0 0 1 19.8 4.2A5.1 5.1 0 0 1 19.8 11.3L18.3 12.8A6.4 6.4 0 0 0 17.9 10.4L18.4 9.9A3.2 3.2 0 0 0 18.4 5.6A3.2 3.2 0 0 0 14.1 5.6L10.6 9.2A2.9 2.9 0 0 0 10.6 13.4M23 18V20H20V23H18V20H15V18H18V15H20V18M16.2 13.7A4.8 4.8 0 0 0 14.8 9.2A1 1 0 0 0 13.4 10.6A2.9 2.9 0 0 1 13.4 14.8L9.9 18.4A3.2 3.2 0 0 1 5.6 18.4A3.2 3.2 0 0 1 5.6 14.1L6.1 13.7A7.3 7.3 0 0 1 5.7 11.2L4.2 12.7A5.1 5.1 0 0 0 4.2 19.8A5.1 5.1 0 0 0 11.3 19.8L13.1 18A6 6 0 0 1 16.2 13.7Z" /></svg>
										<div>
											<h1><?php echo $top_referrer; ?></h1>
											<p>Top referrer</p>
										</div>
									</div>
								</li>
								<li class="list-inline-item">
									<div class="stats-header">
										<svg viewBox="0 0 24 24"><path d="M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z" /></svg>
										<div>
											<h1><?php echo $top_lang; ?></h1>
											<p>Top language</p>
										</div>
									</div>
								</li>
							</ul>
						</div>
						<div class="col-12">
							<hr>
						</div>
						<div class="col-12">
							<div id="single-record">
								<p id="preview-record-date"></p>
								<h2 id="preview-record-slug"></h2>
								<span><a id="preview-record-url" href="#" target="_blank" rel="noopener"></a></span>
								<div class="row">
									<div class="col-12 col-no-gutters" style="height: 220px;">
										<div class="ct-chart ct-perfect-fourth red-chart" style="height: 220px;"></div>
									</div>
									<div class="col-6 col-no-gutters">
										<div class="pie-chart1 ct-perfect-fourth"></div>
									</div>
									<div class="col-6 col-no-gutters">
										<div class="pie-chart2 ct-perfect-fourth"></div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
window.onload = function() {
	/** Initial chart and changing charts content */
	jQuery(function()
	{
		var bar_chart_height = 200;
		var bar_chart_labels = [<?php for($i=1; $i <= $date['days']; $i++){echo ($i > 1 ? ', ': '').'\''.$i.'\'';} ?>];
		var bar_chart_series = [];

		var prev_record = jQuery('#first-record').data();
		bar_chart_series = prev_record.daily.split('/');
		jQuery('#preview-record-slug').html('/'+prev_record.slug);
		jQuery('#preview-record-date').html(prev_record.date);
		jQuery('#preview-record-url').attr('href', prev_record.url);
		jQuery('#preview-record-url').html(prev_record.url);

		new Chartist.Bar('.ct-chart', {labels: bar_chart_labels,series: [bar_chart_series]},{height: bar_chart_height,axisX: {position: 'start'},axisY: {position: 'end'}});

		jQuery('.links-card').on('click', function()
		{
			var data = jQuery(this).data();
			jQuery('#preview-record-slug').html('/'+data.slug);
			jQuery('#preview-record-date').html(data.date);
			jQuery('#preview-record-url').attr('href', data.url);
			jQuery('#preview-record-url').html(data.url);

			new Chartist.Bar('.ct-chart', {labels: bar_chart_labels,series: [data.daily.split('/')]},{height: bar_chart_height,axisX: {position: 'start'},axisY: {position: 'end'}});
		});
	});

	/** Circle charts */
	var data = {
	  series: [5, 3, 4]
	};

	var sum = function(a, b) { return a + b };

	new Chartist.Pie('.pie-chart1', data, {
		donut: true,
		donutWidth: 90,
	  labelInterpolationFnc: function(value) {
	    return Math.round(value / data.series.reduce(sum) * 100) + '%';
	  }
	});

	new Chartist.Pie('.pie-chart2', data, {
		donut: true,
		donutWidth: 90,
	  labelInterpolationFnc: function(value) {
	    return Math.round(value / data.series.reduce(sum) * 100) + '%';
	  }
	});


	/** AJAX - Add new record */
	jQuery(function()
	{
		jQuery('#add-record-send').on('click', function(e){
			e.preventDefault();
			if(jQuery('#add-alert').is(':visible')){jQuery('#add-alert').slideToggle();}
			if(jQuery('#add-success').is(':visible')){jQuery('#add-success').slideToggle();}
			jQuery.ajax({
				url: '<?php echo $this->home_url().'dashboard/ajax/'; ?>',
				type:'post',
				data:$("#add-record-form").serialize(),
				success:function(e)
				{
					if(e == 'success')
					{
						jQuery('#add-success').slideToggle();

						jQuery('#total_records_count').html(parseInt(jQuery('#total_records_count').html()) + 1);


						var slug = jQuery('#forward-slug').val();
						if(slug == '')
						{
							slug = jQuery('#randValue').val();
						}
						var url = '<?php echo $this->home_url(); ?>'+slug;
						var target = jQuery('#forward-url').val();
						var target_shorted = jQuery('#forward-url').val();
						var date = '<?php echo date('Y-m-d', time()) ?>';

						jQuery("#records_list div:first").after('<div class="card links-card"><div class="card-body"><div><small>'+date+'</small><h2><a target="_blank" rel="noopener" href="'+url+'">/'+slug+'</a></h2><p><a target="_blank" rel="noopener" href="'+target_shorted+'">'+target+'...</a></p></div><span>0</span></div></div>');;

						window.setTimeout(function(){
							jQuery('#add-success').slideToggle()
						}, 3000);
					}else{
						var error_text = 'Unknown';

						if(e == 'error_2')
						{
							error_text = 'The form verification key is invalid.';
						}
						else if(e == 'error_3')
						{
							error_text = 'The form content is not valid.';
						}
						else if(e == 'error_4')
						{
							error_text = 'You must provide a url.';
						}
						else if(e == 'error_5')
						{
							error_text = 'This url has already been added.';
						}
						else if(e == 'error_6')
						{
							error_text = 'Shortcut with this slug already exists.';
						}

						jQuery('#error_text').html(error_text);
						jQuery('#add-alert').slideToggle();
					}
					console.log(e);
				},
				fail:function(xhr, textStatus, errorThrown){
					console.log(xhr);
					console.log(textStatus);
					console.log(errorThrown);
					jQuery('#add-alert').slideToggle();
				}
			});
		});
	});
};
</script>
<?php $this->footer(); ?>