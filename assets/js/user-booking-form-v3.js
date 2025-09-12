(function($){
    'use strict';

    function UBFv3(selector){
        this.form = $(selector);
        this.current = 1;
        this.total = this.form.find('.ubf-form-step').length;
        this.init();
    }

    UBFv3.prototype.init = function(){
        const self = this;
        this.showStep(1);

        this.form.on('click', '.ubf-next', function(e){
            e.preventDefault();
            self.next();
        });
        this.form.on('click', '.ubf-prev', function(e){
            e.preventDefault();
            self.prev();
        });

        this.form.on('submit', function(e){
            e.preventDefault();
            self.submit();
        });

        // payment option click to toggle radio
        this.form.on('click', '.payment-option', function(){
            const $input = $(this).find('input[type="radio"]');
            if ($input.length){
                $input.prop('checked', true).trigger('change');
                // update visual
                $(this).closest('.payment-methods').find('.payment-option').removeClass('selected');
                $(this).addClass('selected');
            }
        });

        // sync visual on change (in case user clicks the label/input directly)
        this.form.on('change', 'input[name="payment_method"]', function(){
            const val = $(this).val();
            const $group = $(this).closest('.payment-methods');
            $group.find('.payment-option').removeClass('selected');
            $group.find('input[value="'+val+'"]').closest('.payment-option').addClass('selected');
        });

        // update progress on load
        this.updateProgress();
    }

    UBFv3.prototype.showStep = function(step){
        this.form.find('.ubf-form-step').hide();
        this.form.find('.ubf-form-step[data-step="'+step+'"]').show();
        this.current = step;
        this.updateStepper();
        this.updateProgress();
    }

    /**
     * Validate schedule: date not in past, time between business hours, and if date is today time must be future
     */
    UBFv3.prototype.validateSchedule = function(){
        const $date = this.form.find('#ubf_preferred_date');
        const $time = this.form.find('#ubf_preferred_time');
        const $err = this.form.find('.ubf-schedule-error');

        $err.hide().text('');

        const dateVal = $date.val();
        const timeVal = $time.val();

        if (!dateVal && !timeVal){
            // allow empty schedule (optional) but if one is provided the other must be present
            return true;
        }

        if (!dateVal){
            $err.text('Please select a preferred date.').show();
            return false;
        }
        if (!timeVal){
            $err.text('Please select a preferred time.').show();
            return false;
        }

        // business hours from attributes (default 08:00 - 18:00)
        const businessStart = $date.attr('data-business-start') || '08:00';
        const businessEnd = $date.attr('data-business-end') || '18:00';

        // parse dates
        const selectedDate = new Date(dateVal + 'T' + timeVal);
        const now = new Date();

        // check past datetime
        if (selectedDate < now){
            $err.text('Selected date/time is in the past. Please choose a future time.').show();
            return false;
        }

        // compare time only to business hours
        const timeOnly = timeVal;
        if (timeOnly < businessStart || timeOnly > businessEnd){
            $err.text('Selected time is outside of business hours ('+businessStart+' - '+businessEnd+').').show();
            return false;
        }

        // if date is today, ensure time is in the future (already covered by selectedDate < now)

        return true;
    }

    UBFv3.prototype.updateStepper = function(){
        const steps = this.form.closest('.ubf-v3-form-wrapper').find('.ubf-step');
        steps.removeClass('active');
        steps.each(function(){
            const s = parseInt($(this).attr('data-step'),10);
            if (s === window.ubfTempCurrent) return; // unused
        });
        steps.filter('[data-step="'+this.current+'"]').addClass('active');
    }

    UBFv3.prototype.updateProgress = function(){
        const denom = Math.max(this.total-1,1);
        const pct = ((this.current-1)/denom)*100;
        this.form.closest('.ubf-v3-form-wrapper').find('.ubf-progress-fill').css('width', pct+'%');
    }

    UBFv3.prototype.next = function(){
        // if moving from step 3 -> 4 validate schedule
        if (this.current === 3){
            const scheduleOK = this.validateSchedule();
            if (!scheduleOK) return; // don't proceed
        }

        if (this.current < this.total){
            this.showStep(this.current+1);
        }
    }

    UBFv3.prototype.prev = function(){
        if (this.current > 1){
            this.showStep(this.current-1);
        }
    }

    UBFv3.prototype.submit = function(){
        const self = this;
        // basic validation
        const name = this.form.find('#ubf_customer_name').val() || '';
        const email = this.form.find('#ubf_customer_email').val() || '';
        const service = this.form.find('#ubf_service_id').val() || '';
        if (!name || !email || !service){
            alert('Please fill required fields: service, name and email');
            return;
        }

        // validate schedule one more time before submitting
        if (!this.validateSchedule()){
            // if invalid schedule, jump to step 3
            this.showStep(3);
            return;
        }

        const data = this.form.serializeArray();
        data.push({name:'action',value:'submit_user_booking_v3'});
        data.push({name:'nonce',value:userBookingV3.nonce});

        $.ajax({
            url: userBookingV3.ajaxurl,
            method: 'POST',
            data: $.param(data),
            success: function(resp){
                if (resp && resp.success){
                    // show inline success UI
                    $('.ubf-v3-form-wrapper').hide();
                    $('#ubf-v3-success').fadeIn();
                    // attach actions
                    $('#ubf-v3-success-new').off('click').on('click', function(){
                        $('#ubf-v3-success').hide();
                        $('.ubf-v3-form-wrapper').show();
                        self.form[0].reset();
                        self.showStep(1);
                    });
                    $('#ubf-v3-success-view').off('click').on('click', function(){
                        // try to go to booking history if available
                        if (userBookingV3.bookingHistoryUrl) {
                            window.location.href = userBookingV3.bookingHistoryUrl;
                        } else {
                            // fallback: just hide success and show form
                            $('#ubf-v3-success').hide();
                            $('.ubf-v3-form-wrapper').show();
                        }
                    });
                } else {
                    alert(resp && resp.message ? resp.message : userBookingV3.messages.error);
                }
            },
            error: function(){
                alert(userBookingV3.messages.error);
            }
        });
    }

    $(function(){
        if ($('#user-booking-form-v3').length){
            new UBFv3('#user-booking-form-v3');
        }
    });

})(jQuery);
