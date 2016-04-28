<?php

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.enhancedLegendRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");

$refSymbol = 'BTC';
if ($coin->symbol == 'BTC') $refSymbol = 'USD';

echo <<<end

<style type="text/css">
#graph_history_price, #graph_history_balance {
	width: 75%; height: 300px; float: right;
	margin-bottom: 8px;
}
.jqplot-title {
	margin-bottom: 4px;
}
.jqplot-cursor-tooltip,
.jqplot-highlighter-tooltip {
	background: rgba(220,220,220, .6) !important;
	border: 1px solid gray;
	padding: 2px 4px;
	z-index: 100;
}
.jqplot-xaxis-tick {
	margin-top: 4px;
}
.jqplot-y2axis-tick {
	font-size: 7pt;
	margin-top: -4px;
	margin-left: 8px;
	width: 36px;
}
.jqplot-table-legend-swatch {
	height: 8px;
	width: 8px;
	margin-top: 2px;
	margin-left: 16px;
}
</style>

<div class="graph" id="graph_history_price"></div>
<div class="graph" id="graph_history_balance"></div>

<script type="text/javascript">

var last_graph_update = 0;

function graph_refresh()
{
	var now = Date.now()/1000;

	if (now < last_graph_update + 1) return;
	last_graph_update = now;

	var w = 0 + $('div#graph_history_price').parent().width();
	w = w - $('div#sums').width() - 32;
	$('.graph').width(w);

	var url = "/site/graphMarketBalance?id={$coin->id}";
	$.get(url, '', graph_balance_data);

	var url = "/site/graphMarketPrices?id={$coin->id}";
	$.get(url, '', graph_price_data);
}

function graph_price_data(data)
{
	var t = $.parseJSON(data);
	var graph = $.jqplot('graph_history_price', t.data,
	{
		title: '<b>Price history</b>',
		animate: false, animateReplot: false,
		axes: {
			xaxis: {
				show: true,
				tickInterval: 600,
				tickOptions: { fontSize: '7pt', escapeHTML: false },
				renderer: $.jqplot.DateAxisRenderer
			},
			x2axis: {
				// hidden (top) axis with higher granularity
				syncTicks: 1,
				tickInterval: 600,
				tickOptions: { show: false },
				renderer: $.jqplot.DateAxisRenderer
			},
			y2axis: {
				min: t.rangeMin, max: t.rangeMax
			}
		},

		seriesDefaults: {
			xaxis: 'x2axis',
			yaxis: 'y2axis',
			markerOptions: { style: 'circle', size: 2 }
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 0, shadowDepth: 0,
			background: '#f0f0f0'
		},

		legend: {
			labels: t.labels,
			renderer: jQuery.jqplot.EnhancedLegendRenderer,
			rendererOptions: { numberRows: 1 },
			location: 'n',
			show: true
		},

		highlighter: {
			useAxesFormatters: false,
			tooltipContentEditor: function(str, seriesIndex, pointIndex, jqPlot) {
				var pt = jqPlot.series[seriesIndex].data[pointIndex];
				var dt = new Date(0+pt[0]);
				var date = $.datepicker.formatDate('dd M yy', dt);
				var time = $.jsDate.strftime(dt, '%H:%M');
				return date+' '+time+' '+ t.labels[seriesIndex] + '<br/>' + pt[1]+' {$refSymbol}';
			},
			show: true
		}
	});
	// limit visible axis ticks
	var x2ticks = graph.axes.x2axis._ticks;
	graph.axes.xaxis.ticks = [];
	var tickInterval = graph.grid._width > 0 ? Math.round(60*300 / graph.grid._width, 0) : 1;
	var label, day, lastDay;
	for (var i=0; i < x2ticks.length; i++) {
		if (i % tickInterval == 0) {
			var dt = new Date(0+x2ticks[i].value);
			day = '<b>'+$.datepicker.formatDate('dd M', dt)+'</b>';
			label = (day == lastDay) ? $.jsDate.strftime(dt, '%H:%M') : day;
			lastDay = day;
			graph.axes.xaxis.ticks.push([x2ticks[i].value, label]);
		}
	}
	graph.axes.xaxis.ticks.push([x2ticks[x2ticks.length-1].value, '']);
	graph.replot(false);
}

function graph_balance_data(data)
{
	var t = $.parseJSON(data);
	var graph = $.jqplot('graph_history_balance', t.data,
	{
		title: '<b>Balances</b>',
		animate: false, animateReplot: false,
		stackSeries: true,
		axes: {
			xaxis: {
				show: true,
				tickInterval: 600,
				tickOptions: { fontSize: '7pt', escapeHTML: false },
				showMinorTicks: false,
				renderer: $.jqplot.DateAxisRenderer
			},
			x2axis: {
				// hidden (top) axis with higher granularity
				syncTicks: 1,
				tickInterval: 600,
				tickOptions: { show: false },
				renderer: $.jqplot.DateAxisRenderer
			},
			y2axis: {
				syncTicks: 1,
				min: t.rangeMin, max: t.rangeMax
			}
		},

		seriesDefaults: {
			xaxis: 'x2axis',
			yaxis: 'y2axis',
			fill: true
		},

		grid: {
			borderWidth: 1,
			shadowWidth: 0, shadowDepth: 0,
			background: '#f0f0f0'
		},

		legend: {
			labels: t.labels,
			renderer: jQuery.jqplot.EnhancedLegendRenderer,
			rendererOptions: { numberRows: 1 },
			location: 'n',
			show: true
		},

		highlighter: {
			useAxesFormatters: false,
			tooltipContentEditor: function(str, seriesIndex, pointIndex, jqPlot) {
				var pt = jqPlot.series[seriesIndex].data[pointIndex];
				var dt = new Date(0+pt[0]);
				var date = $.datepicker.formatDate('dd M yy', dt);
				var time = $.jsDate.strftime(dt, '%H:%M');
				return date+' '+time+' '+ t.labels[seriesIndex] + '<br/>' + pt[1]+' {$coin->symbol}';
			},
			show: true
		}
	});
	// limit visible axis ticks
	var x2ticks = graph.axes.x2axis._ticks;
	graph.axes.xaxis.ticks = [];
	var tickInterval = graph.grid._width > 0 ? Math.round(60*300 / graph.grid._width, 0) : 1;
	var label, day, lastDay;
	for (var i=0; i < x2ticks.length; i++) {
		if (i % tickInterval == 0) {
			var dt = new Date(0+x2ticks[i].value);
			day = '<b>'+$.datepicker.formatDate('dd M', dt)+'</b>';
			label = (day == lastDay) ? $.jsDate.strftime(dt, '%H:%M') : day;
			lastDay = day;
			graph.axes.xaxis.ticks.push([x2ticks[i].value, label]);
		}
	}
	graph.axes.xaxis.ticks.push([x2ticks[x2ticks.length-1].value, '']);
	graph.replot(false);
}
</script>
end;

JavascriptReady("graph_refresh(); $(window).resize(graph_refresh);");