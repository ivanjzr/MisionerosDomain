(function ($) {
    'use strict';





    //
    function onEditReady(section_data, opts){

        //
        $('#company_name').val(section_data.company_name);
        $('#contact_name').val(section_data.contact_name);
        $('#notes').val(section_data.notes);

        //
        if (section_data.active){
            $('#active').attr("checked", true);
        } else {
            $('#active').attr("checked", false);
        }
        //
        $('.section-title').text(section_data.company_name);



        // def focus
        $("#company_name").focus();
    }






    /*
    *
    * SECCION EDIT ADMINISTRATORS
    *
    * */
    app.createSection({
        section_title: "Accounts",
        section_title_singular: "Account",
        scripts_path: "/sup/accounts",
        endpoint_url: app.supadmin_url + "/accounts",
        record_id: record_id,
        onEditReady: onEditReady,
        reloadDataOnSave: true
    });




})(jQuery);