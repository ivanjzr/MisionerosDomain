define(function(){
    function moduleReady(modal, section_data){
        //console.log("------setion_data: ", section_data);
        
        //
        app.selectedPromo = null;

        

        

        //
        $.S2Ext({
            S2ContainerId: "promo_id",
            placeholder: "...buscar promocion",
            language: {
                noResults: function(){ return ""; },
                searching: function(){ return ""; }
            },
            dropdownParent: $('#modal-select-promo .modal'),
            allowClear: true,
            minimumInputLength: 2,
            minimumResultsForSearch: "-1",
            remote: {
                qs: function(){
                    return {};
                },
                url: app.admin_url + "/promos/search",
                dataType: 'json',
                delay: 250,
                processResults: function (response, page) {
                    return {
                        results: response
                    };
                },
                cache: false,
                templateResult: app.templateResultPromos,
                templateSelection: app.templateSelectionPromos,
            },
            onChanged: function(sel_id, data){
                //console.log('Promo seleccionada:', sel_id, data);
                app.selectedPromo = data;
                //
                $(document).trigger('promoSelected', [app.selectedPromo]);                
                $("#modal-select-promo").find('.modal').modal("hide");
            },
            onClose: function(){
                //
                app.selectedPromo = null;
            }
        });



        

        //
        $('#form_select_promo').validate();
        //
        $('#form_select_promo').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();            
            //
            if ( $('#form_select_promo').valid() ) {
                //
                $(document).trigger('promoSelected', [app.selectedPromo]);
                $("#modal-select-promo").find('.modal').modal("hide");
            }
        });






    }
    return {init: moduleReady}
});