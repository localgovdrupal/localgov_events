/**
 * @file
 * Events date picker functionality.
 */

(function localgovEventsDatePickerScript($) {
  Drupal.behaviors.localgovEventsDatePicker = {
    attach: function attach() {
      $('.js-date-picker').on('change', (event) => {
        let startDate = null;
        let endDate = null;
        const today = new Date();

        switch (event.target.value) {
          case 'today': {
            startDate = today;
            endDate = today;
            break;
          }

          case 'tomorrow': {
            const tomorrow = today.setDate(today.getDate() + 1);
            startDate = new Date(tomorrow);
            endDate = new Date(tomorrow);
            break;
          }

          case 'this_week': {
            // First day is the day of the month - the day of the week.
            const first = today.getDate() - today.getDay() + 1;
            // Last day is the first day + 6.
            const last = first + 6;
            startDate = new Date(today.setDate(first));
            endDate = new Date(today.setDate(last));
            break;
          }

          case 'this_month': {
            startDate = new Date(today.getFullYear(), today.getMonth(), 1, 1);
            endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0, 1);
            break;
          }

          case 'next_month': {
            if (today.getMonth() < 11) {
              startDate = new Date(
                today.getFullYear(),
                today.getMonth() + 1,
                1,
                1,
              );
              endDate = new Date(
                today.getFullYear(),
                today.getMonth() + 2,
                0,
                1,
              );
            } else {
              startDate = new Date(today.getFullYear() + 1, 0, 1, 1);
              endDate = new Date(today.getFullYear() + 1, 1, 0, 1);
            }
            break;
          }
          default: {
            startDate = null;
            endDate = null;
          }
        }

        if (startDate) {
          $('.js-date-picker-start').val(startDate.toISOString().substr(0, 10));
        } else {
          $('.js-date-picker-start').val('');
        }
        if (endDate) {
          $('.js-date-picker-end').val(endDate.toISOString().substr(0, 10));
        } else {
          $('.js-date-picker-end').val('');
        }
      });
    },
  };
})(jQuery);
