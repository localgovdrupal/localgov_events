/**
 * @file
 * Events date picker functionality.
 */

(function ($) {
  Drupal.behaviors.localgovEventsDatePicker = {
    attach: function attach(context, settings) {

      $('.js-date-picker').on('change', function() {
        console.log(this.value);

        let start_date = null;
        let end_date = null;
        const today = new Date();

        switch(this.value) {

          case 'today':
            start_date = today;
            end_date = today;
            break;

          case 'tomorrow':
            start_date = new Date(today.getDate() + 1);
            end_date = new Date(today.getDate() + 1);
            break;

          case 'this_week':
            const first = today.getDate() - today.getDay() + 1; // First day is the day of the month - the day of the week
            const last = first + 6; // last day is the first day + 6
            start_date = new Date(today.setDate(first));
            end_date = new Date(today.setDate(last));
            break;

          case 'this_month':
            start_date = new Date(today.getFullYear(), today.getMonth() , 1, 1);
            end_date = new Date(today.getFullYear(), today.getMonth() + 1, 0, 1);
            break;

          case 'next_month':
            if (today.getMonth() < 11) {
              start_date = new Date(today.getFullYear(), today.getMonth() + 1, 1, 1);
              end_date = new Date(today.getFullYear(), today.getMonth() + 2, 0, 1);
            }
            else {
              start_date = new Date(today.getFullYear() + 1, 0, 1, 1);
              end_date = new Date(today.getFullYear() + 1, 1, 0, 1);
            }
            break;

        }

        if (start_date) {
          $('.js-date-picker-start').val(start_date.toISOString().substr(0, 10));
        }
        else {
          $('.js-date-picker-start').val('');
        }
        if (end_date) {
          $('.js-date-picker-end').val(end_date.toISOString().substr(0, 10));
        }
        else {
          $('.js-date-picker-end').val('');
        }
      });

    }
  }
})(jQuery);
