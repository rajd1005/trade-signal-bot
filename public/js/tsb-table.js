jQuery(document).ready(function($) {
    
    // 1. High/Low Edit Icon Click -> Show Input
    $(document).on('click', '.hl-edit-btn', function(e) {
        e.preventDefault();
        if($(this).attr('disabled')) return;
        
        var row = $(this).closest('div');
        var input = row.find('.hl-input');
        
        // Hide Display Text, Hide Edit Pencil, Show Input, Focus Input
        row.find('.hl-display').hide();
        $(this).hide();
        input.show().focus();
    });

    // 2. High/Low Input Blur (Close Input / Click Outside)
    $(document).on('blur', '.hl-input', function() {
        var input = $(this);
        var val = input.val();
        var row = input.closest('div');
        
        // Always hide input on blur
        input.hide();
        
        // Update display text (Show '-' if 0 or empty)
        var displayVal = (val && parseFloat(val) > 0) ? val : '-';
        row.find('.hl-display').text(displayVal).show();
        
        // Show Edit Button again
        row.find('.hl-edit-btn').show();
    });

    // 2.1 Handle "Enter" Key to trigger blur/save
    $(document).on('keyup', '.hl-input', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) {
            $(this).blur(); // Trigger the blur event above
        }
    });

    // 3. Live Update (High/Low) - WITH VALIDATION & STATS UPDATE
    $(document).on('change', '.live-update', function() {
        var input = $(this);
        var val = parseFloat(input.val());
        var type = input.data('field'); // high_price or low_price
        var row = input.closest('tr');
        
        // Validation Data
        var entry = parseFloat(row.attr('data-entry'));
        var t1 = parseFloat(row.attr('data-t1'));
        var t2 = parseFloat(row.attr('data-t2'));
        var t3 = parseFloat(row.attr('data-t3'));
        var status = row.attr('data-status');
        
        // High Validation
        if(type === 'high_price') {
            if(val > 0 && val <= entry) {
                alert("High must be greater than Entry Price (" + entry + ")");
                input.val(0).trigger('blur'); return; 
            }
            if(status === 'T1' && val <= t1) {
                alert("Since T1 is done, High must be greater than T1 (" + t1 + ")");
                input.val(0).trigger('blur'); return;
            }
            if(status === 'T2' && val <= t2) {
                alert("Since T2 is done, High must be greater than T2 (" + t2 + ")");
                input.val(0).trigger('blur'); return;
            }
            if(status === 'T3' && val <= t3) {
                alert("Since T3 is done, High must be greater than T3 (" + t3 + ")");
                input.val(0).trigger('blur'); return;
            }
        }

        // Low Validation
        if(type === 'low_price') {
            if(val > 0 && val >= entry) {
                alert("Low must be lower than Entry Price (" + entry + ")");
                input.val(0).trigger('blur'); return;
            }
        }

        // Send Ajax
        $.post(tsb_ajax.ajax_url, { 
            action: 'tsb_update_live', 
            id: input.data('id'), 
            field: type, 
            val: val 
        }, function(res) {
            if(res.success) {
                // 3a. Update Row P/L Text & Color
                if(res.data.pl) {
                    var plSpan = $('#row-'+input.data('id')).find('.pl-text');
                    var plVal = parseFloat(res.data.pl.replace(/,/g,''));
                    plSpan.text(res.data.pl).css('color', plVal >= 0 ? '#2e7d32' : '#c62828');
                }
                
                // 3b. Update Dashboard Stats (Auto Update)
                if(res.data.stats && window.TSB && TSB.updateDashboard) {
                    TSB.updateDashboard(res.data.stats);
                }
            } else {
                // Revert on error
                alert(res.data || 'Error updating');
                input.val(0).trigger('blur');
            }
        });
    });

    // 4. Trigger Actions (Telegram Buttons)
    $(document).on('click', '.tg-act', function(e) {
        e.preventDefault();
        var btn = $(this);
        if(btn.attr('disabled')) return;
        
        var id = btn.data('id');
        var type = btn.data('type');

        $.post(tsb_ajax.ajax_url, { action: 'tsb_trigger_telegram', trade_id: id, type: type }, function(res) {
            if(res.success) {
                // Show Toast
                if(window.TSB && window.TSB.showToast) TSB.showToast('Update Sent!');
                
                // Update Dashboard Live
                if(res.data && window.TSB && TSB.updateDashboard) {
                    TSB.updateDashboard(res.data);
                    
                    // Update Row P/L if returned (e.g. SL hit)
                    if(res.data.row_pl) {
                        var plSpan = $('#row-'+id).find('.pl-text');
                        var plVal = parseFloat(res.data.row_pl.replace(/,/g,''));
                        plSpan.text(res.data.row_pl).css('color', plVal >= 0 ? '#2e7d32' : '#c62828');
                    }
                }
                
                // DOM Logic (Update Buttons/Status based on Type)
                var row = $('#row-'+id);
                var badge = row.find('.status-badge');
                
                // Update Validation Data Attribute
                row.attr('data-status', type); 

                // State Machine for Buttons
                if(type === 'Entry') {
                    badge.text('Active');
                    btn.attr('disabled', true).css('opacity', 0.4);
                    row.find('.edit-entry-btn').remove(); // Cannot edit entry once active
                    // Enable High/Low, SL, T1
                    row.find('.hl-edit-btn, .high-btn, .btn-sl, .btn-t1').removeAttr('disabled').css('opacity', 1).css('pointer-events','auto'); 
                    row.find('.hl-high-input, .hl-low-input').removeAttr('disabled');
                }
                else if(type === 'T1') {
                    badge.text('T1');
                    btn.attr('disabled', true).css('opacity', 0.4);
                    row.find('.btn-sl').attr('disabled', true).css('opacity', 0.4); // SL Inactive
                    row.find('.btn-t2').removeAttr('disabled').css('opacity', 1); // Enable T2
                    // Disable Low Adding
                    row.find('.hl-low-input').attr('disabled', true);
                    row.find('.hl-edit-btn[data-target="low"]').attr('disabled', true).css('opacity', 0.4);
                }
                else if(type === 'SL') {
                    badge.text('SL');
                    btn.attr('disabled', true).css('opacity', 0.4);
                    row.find('.btn-t1, .btn-t2, .btn-t3').attr('disabled', true).css('opacity', 0.4);
                    // Disable High Adding
                    row.find('.hl-high-input').attr('disabled', true);
                    row.find('.hl-edit-btn[data-target="high"]').attr('disabled', true).css('opacity', 0.4);
                    row.find('.high-btn').attr('disabled', true).css('opacity', 0.4);
                }
                else if(type === 'T2') { 
                    badge.text('T2'); 
                    btn.attr('disabled', true).css('opacity', 0.4); 
                    row.find('.btn-t3').removeAttr('disabled').css('opacity', 1); 
                }
                else if(type === 'T3') { 
                    badge.text('T3'); 
                    btn.attr('disabled', true).css('opacity', 0.4); 
                }
            }
        });
    });

    // Delete Logic
    $(document).on('click', '.delete-row', function() {
        if(confirm('Delete Trade & Messages?')) {
            var row = $(this).closest('tr');
            $.post(tsb_ajax.ajax_url, { action: 'tsb_delete_trade', id: $(this).data('id') }, function(res) {
                if(res.success) { 
                    row.remove(); 
                    if(window.TSB && TSB.showToast) TSB.showToast('Deleted');
                    if(res.data && window.TSB) TSB.updateDashboard(res.data); // Update Stats
                }
            });
        }
    });
});