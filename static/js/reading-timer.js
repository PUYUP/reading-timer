(function ($) {
    $( document ).ready( function() {
        var post_id = readingTimerVar.post_id;
        var countdown = readingTimerVar.countdown ? readingTimerVar.countdown : 3; // in minutes
        var countdownTimer = moment().add(countdown, 'minutes').format( 'YYYY/MM/DD HH:mm:ss');
        var $codeEl = $( '#timer-' + post_id + ' #code' );
        var $timerEl = $( "#timer-" + post_id + " #countdown" );
        
        if ( $timerEl.length > 0 ) {
            $timerEl.countdown( countdownTimer , function(event) {
                var totalHours = event.offset.totalDays * 24 + event.offset.hours;
                $(this).html(event.strftime(totalHours + ' jam %M menit %S detik'));
            })
            .on('finish.countdown', function() {
                saveTimer();
                $timerEl.hide();
            });
        }


        // ...
        // SAVE DATE
        // ...
        var saveTimer = function() {
            var code = (Math.random() + 1).toString(36).substring(5);
            var timer = readingTimerVar.countdown ? readingTimerVar.countdown : 3;

            // print code
            $codeEl.html( code );

            $.ajax({
                url: readingTimerVar.ajax_url,
                method: 'POST',
                data: {
                    action: 'reading_timer_save',
                    post_id: post_id,
                    code: code,
                    timer: timer,
                },
                success: function(res) {
                    // console.log(res);
                }
            });
        }
    } );
})(jQuery);