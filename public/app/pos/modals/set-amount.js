define(function(){
    function moduleReady(modal, s_data){
        console.log(s_data);

        //
        $("#set-amount-title").text("Pagar " + s_data.payment_method);
        
        //
        $('#form_set_amount').validate();
        //
        $('#form_set_amount').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            //
            if ( $('#form_set_amount').valid() ) {
                disable_btns();

                //
                if ( $.isFunction(s_data.onContinue) ){
                    //
                    s_data.onContinue($("#new_amount").val());
                }

                //
                $("#modal-set-amount").find('.modal').modal("hide");

                //
                setTimeout(function(){
                    //
                    enable_btns();
                    //
                    var lastInput = $('#tbl_payments tbody tr:last input');
                    if (lastInput.length) {
                        lastInput.focus().select();
                    }
                }, 250);
            }
        });

        //
        $("#new_amount")
            .focus();
        //
        if ($.isNumeric(s_data.amount)){
            $("#new_amount").val(s_data.amount);
        }
        //
        $("#new_amount").on("focus", function(){
            $(this).select();
        });
        //
        setTimeout(function(){
            //
            $("#new_amount")
                .select();
        }, 250);

    }
    return {init: moduleReady}
});