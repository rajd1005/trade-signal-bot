jQuery(document).ready(function($) {
    
    var now = new Date(); 
    $('#trade_date').val(now.toLocaleDateString('en-GB'));
    var m_t1=0.5, m_t2=1.0, m_t3=1.5;

    // 1. Initialize Datepicker
    $('#expiry').datepicker({
        dateFormat: 'd M',
        minDate: 0
    });

    // Helper: Format Date - 27 Jan (No Brackets)
    function formatDate(d) {
        var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        return d.getDate() + ' ' + months[d.getMonth()];
    }

    // Helper: Get Next Expiry Date
    // Added 'skipToday' parameter
    function getExpiryDate(type, dayName, skipToday) {
        if(!dayName) return '';
        var days = { 'sunday':0, 'monday':1, 'tuesday':2, 'wednesday':3, 'thursday':4, 'friday':5, 'saturday':6 };
        var targetDay = days[dayName.toLowerCase()];
        if(targetDay === undefined) return '';

        var d = new Date();
        d.setHours(0,0,0,0);

        if (type === 'Weekly') {
            var today = d.getDay();
            var diff = targetDay - today;
            
            // If skipToday is ON: if diff <= 0 (Today or Past), move to next week
            // If skipToday is OFF: if diff < 0 (Past), move to next week. (Today stays today)
            if (skipToday == 1) {
                if (diff <= 0) diff += 7;
            } else {
                if (diff < 0) diff += 7;
            }
            
            d.setDate(d.getDate() + diff);
            return formatDate(d);

        } else if (type === 'Monthly') {
            // Check current month expiry
            var lastDayThisMonth = getLastDayOfMonth(d.getFullYear(), d.getMonth(), targetDay);
            
            var useNextMonth = false;
            
            if (skipToday == 1) {
                // If Today is >= LastDay (meaning today IS the day or it's passed), use next month
                if (d >= lastDayThisMonth) {
                    useNextMonth = true;
                }
            } else {
                // If Today is > LastDay (meaning strictly passed), use next month
                if (d > lastDayThisMonth) {
                    useNextMonth = true;
                }
            }

            if (useNextMonth) {
                var nextMonth = new Date(d.getFullYear(), d.getMonth() + 1, 1);
                var lastDayNextMonth = getLastDayOfMonth(nextMonth.getFullYear(), nextMonth.getMonth(), targetDay);
                return formatDate(lastDayNextMonth);
            } else {
                return formatDate(lastDayThisMonth);
            }
        }
        return '';
    }

    // Helper: Find Last occurrence of a weekday in a specific month
    function getLastDayOfMonth(year, month, targetDayIdx) {
        var d = new Date(year, month + 1, 0); // Last day of month
        // Walk backwards until we find the target day
        while (d.getDay() !== targetDayIdx) {
            d.setDate(d.getDate() - 1);
        }
        return d;
    }

    // Stock Fetch
    $('#stock_name').on('change', function() {
        var val = $(this).val();
        
        if(val) {
            $.post(tsb_ajax.ajax_url, { 
                action: 'tsb_get_stock_details', 
                symbol: val 
            }, function(res) {
                if(res.success) {
                    $('#sl_points_db').val(res.data.stop_loss); $('#lot_size').val(res.data.lot_size);
                    $('#view_lot_size').val(res.data.lot_size); $('#view_sl').val(res.data.stop_loss);
                    
                    // Auto-Populate Expiry
                    if(res.data.expiry_day) {
                        var type = res.data.expiry_type ? res.data.expiry_type : 'Monthly'; 
                        // Pass skip_today setting
                        var nextDate = getExpiryDate(type, res.data.expiry_day, res.data.skip_today);
                        if(nextDate) $('#expiry').val(nextDate);
                    }

                    m_t1 = parseFloat(res.data.m_t1); m_t2 = parseFloat(res.data.m_t2); m_t3 = parseFloat(res.data.m_t3);
                    if($('#entry_price').val()) $('#entry_price').trigger('input');
                }
            });
        }
    });

    // Calc
    $('#entry_price').on('input', function() {
        var e = parseFloat($(this).val()); var s = parseFloat($('#sl_points_db').val());
        if(!isNaN(e) && !isNaN(s)) {
            $('#sl_price').val((e - s).toFixed(2));
            $('#t1').val((e + (s * m_t1)).toFixed(2)); 
            $('#t2').val((e + (s * m_t2)).toFixed(2)); 
            $('#t3').val((e + (s * m_t3)).toFixed(2));
        }
    });

    $(document).on('click', '.toggle-group .type-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var group = btn.closest('.toggle-group');
        group.find('.type-btn').removeClass('active');
        btn.addClass('active');
        $('#ce_pe').val(btn.data('val'));
    });

    $(document).on('click', '.details-toggle', function(e){ 
        e.preventDefault();
        var form = $(this).closest('#tsb-trade-form');
        form.find('.details-content').slideToggle(); 
    });

    // Submit
    $('#tsb-trade-form').submit(function(e) {
        e.preventDefault();
        
        // Basic Client Validation
        if(!$('#stock_name').val() || !$('#entry_price').val() || !$('#sl_price').val()) {
            alert('Please fill all mandatory fields'); return;
        }

        var full_sym = $('#stock_name').val() + ' ' + $('#strike_price').val() + ' ' + $('#ce_pe').val() + ' ' + $('#expiry').val();
        var data = {
            action: 'tsb_submit_trade', 
            channel_name: $('#channel_name').val(), 
            symbol_display: full_sym,
            lot_size: $('#lot_size').val(), 
            entry_price: $('#entry_price').val(), 
            sl_price: $('#sl_price').val(),
            t1: $('#t1').val(), t2: $('#t2').val(), t3: $('#t3').val()
        };
        $.post(tsb_ajax.ajax_url, data, function(res) {
            if(res.success) { 
                if(window.TSB && window.TSB.showToast) TSB.showToast('Trade Published!');
                if(res.data.html) $('#journal-body').prepend(res.data.html); 
                if(res.data.stats && window.TSB && TSB.updateDashboard) {
                    TSB.updateDashboard(res.data.stats); 
                }
                $('#entry_price').val('');
            } else {
                alert(res.data || 'Error');
            }
        });
    });
});