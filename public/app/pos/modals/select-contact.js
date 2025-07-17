define(function(){
    function moduleReady(modal, itemData, modalOptions){
        console.log("------select employee itemData: ", itemData);
        
        

        var callbacks = modalOptions || {};

        //
        itemData.employee = null;

        
        //
        $.S2Ext({
            S2ContainerId: "employee_id",
            placeholder: "...buscar empleado",
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
                url: app.admin_url + "/employees/search",
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
                //console.log('Empleado seleccionado:', sel_id, data);
                itemData.employee = data;
                
                
                callbacks.onSubmit(itemData);
                //$(document).trigger('onEmployeeSelected', [itemData]);


                $("#modal-select-contact").find('.modal').modal("hide");

            },
            onClose: function(){
                //
                itemData.employee = null;
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
                
                
                callbacks.onSubmit(itemData);
                //$(document).trigger('onEmployeeSelected', [itemData]);


                $("#modal-select-contact").find('.modal').modal("hide");
            }
        });




        // focus s2
        setTimeout(function(){
            $('#employee_id').select2('open');
            $('.select2-search__field').focus();
        }, 250);


    }
    return {init: moduleReady}
});