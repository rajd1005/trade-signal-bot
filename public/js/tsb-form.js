jQuery(document).ready(function($) {
    
    var now = new Date(); 
    $('#trade_date').val(now.toLocaleDateString('en-GB'));
    var m_t1=0.5, m_t2=1.0, m_t3=1.5;

    // Stock Fetch (Updated to 'change' for Select dropdown)
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
        var full_sym = $('#stock_name').val() + ' ' + $('#strike_price').val() + ' ' + $('#ce_pe').val() + ' (' + $('#expiry').val() + ')';
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
                if(res.data.stats) TSB.updateDashboard(res.data.stats); 
                $('#entry_price').val('');
            } else {
                alert(res.data || 'Error');
            }
        });
    });
});