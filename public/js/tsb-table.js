jQuery(document).ready(function($) {
    
    // 1. Edit Entry Logic (Browser Popup)
    $(document).on('click', '.edit-entry-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        var row = btn.closest('tr');
        var currentEntry = parseFloat(row.attr('data-entry'));

        var newEntry = prompt("Enter New Entry Price:", currentEntry);
        
        if(newEntry !== null) {
            var val = parseFloat(newEntry);
            if(!isNaN(val) && val > 0 && val !== currentEntry) {
                $.post(tsb_ajax.ajax_url, {
                    action: 'tsb_update_entry',
                    id: id,
                    new_entry: val
                }, function(res) {
                    if(res.success) {
                        if(res.data && res.data.html) {
                            $('#row-' + id).replaceWith(res.data.html);
                            if(window.TSB && TSB.showToast) TSB.showToast('Entry Updated');
                            if(res.data.stats && window.TSB && TSB.updateDashboard) {
                                TSB.updateDashboard(res.data.stats);
                            }
                        } else {
                            location.reload(); 
                        }
                    } else {
                        alert(res.data || 'Update Failed');
                    }
                });
            }
        }
    });

    // 2. High/Low Update Logic (Browser Popup)
    $(document).on('click', '.hl-edit-btn', function(e) {
        e.preventDefault();
        var btn = $(this);
        if(btn.attr('disabled')) return;

        var id = btn.data('id');
        var target = btn.data('target'); // 'high' or 'low'
        var field = target + '_price'; // 'high_price' or 'low_price'
        var row = btn.closest('tr');
        
        var currentValStr = row.find('.hl-' + target + '-display').text();
        var currentVal = (currentValStr === '-') ? 0 : parseFloat(currentValStr);
        
        var promptText = (target === 'high') ? "Enter HIGH Price:" : "Enter LOW Price:";
        var newVal = prompt(promptText, currentVal);

        if(newVal !== null) {
            var val = parseFloat(newVal);
            if(isNaN(val)) val = 0;

            // Validations
            var entry = parseFloat(row.attr('data-entry'));
            var t1 = parseFloat(row.attr('data-t1'));
            var t2 = parseFloat(row.attr('data-t2'));
            var t3 = parseFloat(row.attr('data-t3'));
            var status = row.attr('data-status');

            if(target === 'high') {
                if(val > 0 && val <= entry) { alert("High must be greater than Entry"); return; }
                if(status === 'T1' && val <= t1) { alert("High must be greater than T1"); return; }
                if(status === 'T2' && val <= t2) { alert("High must be greater than T2"); return; }
                if(status === 'T3' && val <= t3) { alert("High must be greater than T3"); return; }
            }
            if(target === 'low') {
                if(val > 0 && val >= entry) { alert("Low must be lower than Entry"); return; }
            }

            // AJAX Update
            $.post(tsb_ajax.ajax_url, { 
                action: 'tsb_update_live', 
                id: id, 
                field: field, 
                val: val 
            }, function(res) {
                if(res.success) {
                    // Update Row from Server to ensure state is synced
                    if(res.data.html) {
                        row.replaceWith(res.data.html);
                    } else {
                        var displayVal = (val > 0) ? val : '-';
                        row.find('.hl-' + target + '-display').text(displayVal);
                    }

                    if(res.data.stats && window.TSB && TSB.updateDashboard) {
                        TSB.updateDashboard(res.data.stats);
                    }
                } else {
                    alert(res.data || 'Error updating');
                }
            });
        }
    });

    // 3. Trigger Actions
    $(document).on('click', '.tg-act', function(e) {
        e.preventDefault();
        var btn = $(this);
        if(btn.attr('disabled')) return;
        
        var id = btn.data('id');
        var type = btn.data('type');

        $.post(tsb_ajax.ajax_url, { 
            action: 'tsb_trigger_telegram', 
            trade_id: id, 
            type: type 
        }, function(res) {
            if(res.success) {
                if(window.TSB && window.TSB.showToast) TSB.showToast('Update Sent!');
                
                // Update Dashboard Stats
                if(res.data && res.data.stats && window.TSB && TSB.updateDashboard) {
                    TSB.updateDashboard(res.data.stats);
                }
                
                // CRITICAL: Replace table row with new HTML from server
                if(res.data && res.data.html) {
                    $('#row-'+id).replaceWith(res.data.html);
                }
            } else {
                alert('Action failed');
            }
        });
    });

    // 4. Delete Logic
    $(document).on('click', '.delete-row', function() {
        if(confirm('Delete Trade & Messages?')) {
            var row = $(this).closest('tr');
            $.post(tsb_ajax.ajax_url, { 
                action: 'tsb_delete_trade', 
                id: $(this).data('id')
            }, function(res) {
                if(res.success) { 
                    row.remove(); 
                    if(window.TSB && TSB.showToast) TSB.showToast('Deleted');
                    if(res.data && res.data.stats && window.TSB) {
                        TSB.updateDashboard(res.data.stats);
                    }
                } else {
                    alert('Permission Denied via Settings');
                }
            });
        }
    });
});