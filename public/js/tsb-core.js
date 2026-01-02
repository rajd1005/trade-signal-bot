window.TSB = window.TSB || {};

(function($) {
    // 1. Toast
    TSB.showToast = function(msg, isError) {
        var t = $('#tsb-toast');
        t.text(msg).css('background', isError ? '#d32f2f' : '#2e7d32').fadeIn().delay(3000).fadeOut();
    };

    // 2. Dashboard Updater (Includes Accuracy)
    TSB.updateDashboard = function(data) {
        if(data && data.pl !== undefined) {
            // Stats
            var plVal = parseFloat(data.pl.replace(/,/g, ''));
            var color = plVal >= 0 ? '#2e7d32' : '#c62828';
            
            $('#dash-pl').text(data.pl).css('color', color);
            $('#dash-w').text(data.w);
            $('#dash-l').text(data.l);
            $('#dash-p').text(data.p);
            
            // Accuracy
            if(data.acc !== undefined) {
                $('#dash-acc').text(data.acc);
            }
        }
    };
})(jQuery);