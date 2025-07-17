(function ($) {
    'use strict';




    

    //
    function onEditReady(section_data, opts){

        //
        $(".patient-title").html(section_data.name);
        //
        $('#name').val(section_data.name);
        $('#username').val(section_data.username);
        $('#email').val(section_data.email);
        $('#home_address').val(section_data.home_address);
        $('#phone_cc').val(section_data.phone_cc);
        $('#phone_number').val(section_data.phone_number);
        $('#notes').val(section_data.notes);
        //
        if (section_data.active){
            $('#active').attr("checked", true);
        } else {
            $('#active').attr("checked", false);
        }


        //
        if ( section_data.thumb_img_url ){
            //
            $('#img_section_url').attr("src", section_data.thumb_img_url + dynurl());
            $('#img_section_url').attr("data-id", section_data.id);
            $('#img_section_url').css({
                "width":200,
                "height":150
            });
            $('#img_section_container').show();
        } else {
            $('#img_section_url').attr("src", null);
            $('#img_section_container').hide();
        }


        //
        loadSelectAjax({
            id: "#phone_country_id",
            url: app.public_url + "/paises/list",
            parseFields: function(item){
                return "+" + item.phone_cc + " (" + item.abreviado + ")";
            },
            prependEmptyOption: true,
            emptyOptionText: "--select",
            default_value: ((section_data && section_data.phone_country_id) ? section_data.phone_country_id : app.ID_PAIS_EU),
            enable: true
        });


        //
        $("#modal-cust-title").html("Editar Paciente " + section_data.name);        


        // def focus
        $("#name").focus();
    }







    //
    function loadModules(section_data, opts){

        //
        section_data.opts = opts;


        //
        loadModule({
            data: section_data,
            onBeforeLoad: function(){
                disable_btns();
            },
            js_url: "/app/customers/modules/mod_clinical_records.js",
            onInit: function(){
                enable_btns();
            }
        });


        
    }




    //
    app.createSection({
        section_title: "Pacientes",
        section_title_singular: "paciente",
        scripts_path: "/app/customers",
        endpoint_url: app.admin_url + "/customers",
        record_id: record_id,
        onEditReady: onEditReady,
        loadModules: loadModules,
        onSectionReady: function(opts){

            

        }
    });




})(jQuery);