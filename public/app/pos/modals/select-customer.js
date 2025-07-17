define(function(){
    function moduleReady(modal, section_data){
        //console.log("------add customer: ", section_data);
        
        //
        let selectedItem = null;

        
        //
        $.S2Ext({
            S2ContainerId: "customer_id",
            placeholder: "...buscar cliente",
            language: {
                noResults: function(){ return ""; },
                searching: function(){ return ""; }
            },
            dropdownParent: $('#modal-select-contact .modal'),
            allowClear: true,
            minimumInputLength: 2,
            minimumResultsForSearch: "-1",
            remote: {
                qs: function(){
                    return {};
                },
                url: app.admin_url + "/customers/search",
                dataType: 'json',
                delay: 250,
                processResults: function (response, page) {
                    return {
                        results: response
                    };
                },
                cache: false,
                templateResult: app.templateResultContact,
                templateSelection: app.templateSelectionContact,
            },
            onChanged: function(sel_id, data){
                //console.log('Cliente seleccionado:', sel_id, data);
                selectedItem = data;
                //
                $(document).trigger('customerSelected', [selectedItem]);                
                $("#modal-select-contact").find('.modal').modal("hide");
            },
            onClose: function(){
                //
                selectedItem = null;
            }
        });



        

        //
        $('#form_select_contact').validate();
        //
        $('#form_select_contact').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();            
            //
            if ( $('#form_select_contact').valid() ) {
                //
                $(document).trigger('customerSelected', [selectedItem]);                
                $("#modal-select-contact").find('.modal').modal("hide");
            }
        });






    }
    return {init: moduleReady}
});