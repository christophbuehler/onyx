$(function() {
	function valuesToData(colors, headers, values) {
		var data = [];
		for (var i=0; i<headers.length; i++) {
			data.push({
				label: headers[i],
				data: values[i],
				color: colors[i]
			});
		}
		return data;
	}
	
	$('.graph-output').each(function() {
		var colors = $(this).attr('data-colors').split(';');
		var headers = $(this).attr('data-headers').split(';');
		var values = $(this).attr('data-values').split(';');

		var data = valuesToData(colors, headers, values);

		$(this).css({'width': $(this).attr('data-width') + 'px', 'height': $(this).attr('data-height') + 'px'});
        $.plot(this, data, {
			series: {
			pie: {
				show: true,
				tilt: 1,
				highlight: {
					opacity: 0.25
				},
				stroke: {
					color: '#fff',
					width: 2
				},
				startAngle: 2
			}
          },
          legend: {
              show: true,
          position: "ne", 
            labelBoxBorderColor: null,
          margin:[0,15]
          },
        grid: {
          hoverable: true,
          clickable: true
        },
        tooltip: true, //activate tooltip
        tooltipOpts: {
          content: "%s : %y.1",
          shifts: {
            x: 0,
            y: -50
          }
        }
       });
	});
});