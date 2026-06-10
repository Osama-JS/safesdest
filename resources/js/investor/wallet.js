$(function() {
  var $dateRange = $('#dateRange');
  var $from = $('input[name="from"]');
  var $to = $('input[name="to"]');

  if ($dateRange.length && typeof $.fn.daterangepicker !== 'undefined') {
      var start = $from.val() ? moment($from.val()) : null;
      var end = $to.val() ? moment($to.val()) : null;

      $dateRange.daterangepicker({
          opens: 'left',
          autoUpdateInput: false,
          locale: {
            format: 'YYYY-MM-DD',
            separator: ' to ',
            applyLabel: 'Apply',
            cancelLabel: 'Cancel',
            fromLabel: 'From',
            toLabel: 'To',
            customRangeLabel: 'Custom',
            weekLabel: 'W',
            daysOfWeek: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
            monthNames: [
              'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'
            ],
            firstDay: 1
          },
          ranges: {
            'Today': [moment(), moment()],
            'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
            'Last 7 Days': [moment().subtract(6, 'days'), moment()],
            'Last 30 Days': [moment().subtract(29, 'days'), moment()],
            'This Month': [moment().startOf('month'), moment().endOf('month')],
            'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
          },
          startDate: start ? start : moment().startOf('month'),
          endDate: end ? end : moment().endOf('month')
      });

      if(start && end) {
          $dateRange.val(start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
      }

      $dateRange.on('apply.daterangepicker', function(ev, picker) {
          $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
          $from.val(picker.startDate.format('YYYY-MM-DD'));
          $to.val(picker.endDate.format('YYYY-MM-DD'));
      });

      $dateRange.on('cancel.daterangepicker', function(ev, picker) {
          $(this).val('');
          $from.val('');
          $to.val('');
      });
  }
});
