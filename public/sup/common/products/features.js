define(function(){
    function moduleReady(section_data){
        //console.log(section_data);




        //
        function bindEvents(){

            // RESET RADIO IF INPUT IS SELECTED
            $('.other-val').focus(function(e) {
                e.preventDefault();
                //
                var context = $(this).parent().parent().find("input:radio");
                context.removeAttr('checked');
                // set name again
                var input_name = context.attr("name");
                $(this).parent().parent().find("input:text").attr("name", input_name)
            });

            // RESET INPUT IF RADIO IS SELECTED
            $('.radio-val').focus(function(e) {
                //
                var context = $(this).parent().parent().find("input:text");
                context.val("");
                // remove name to prevent send duplicate
                context.removeAttr("name");
            });
        }


        //
        function appendInputOtherValue(input_name, input_id, title, append_to, feature_custom_value){



            // ADD INPUT & SPAN TO LABEL
            var item_label_el = $("<label style='font-size: 14px;font-weight: normal;' />");
            item_label_el.html(title);

            // INPUT
            var item_input_el = $("<input type='text' class='other-val' />");
            item_input_el.attr("id", input_name + "-" + input_id);

            //
            if (feature_custom_value){
                item_input_el.attr("name", "fid-" + input_name);
                item_input_el.val(feature_custom_value);
            }

            var div_el = $("<div style='display:inline-block' />");
            div_el.append(item_label_el);
            div_el.append(item_input_el);

            //
            append_to.append(div_el);
        }



        function appendCheckboxItem(input_name, input_id, title, append_to, feature_value_id){

            // INPUT
            var item_input_el = $("<input type='radio' class='radio-val' style=\"height: 25px;width: 25px;\" />");
            item_input_el.attr("name", "fid-" + input_name);
            item_input_el.attr("id", input_name + "-" + input_id);
            item_input_el.val(input_id);

            //
            if ( input_id === feature_value_id ){
                item_input_el.attr("checked", "checked");
            }


            // SPAN
            var item_span_el = $("<span style='padding: 0 0 0 10px;margin: -25px 0 0 20px;display: block;font-size: 14px;font-weight: normal;' />");
            item_span_el.text(title);

            // ADD INPUT & SPAN TO LABEL
            var item_label_el = $("<label style='padding:0 30px 0 20px' />");
            item_label_el.append(item_input_el);
            item_label_el.append(item_span_el);
            //
            append_to.append(item_label_el);
        }




        //
        function buildFeaturesList(list){
            //console.log(list);

            //
            $("#features_list").html("");

            //
            $.each(list, function(idx, item){
                //console.log(idx, item);


                //
                var row_el = $("<div class='row' style='margin-bottom:15px; padding-bottom: 15px; border-bottom:1px solid #e8e8e8' />");
                var col_el = $("<div class='col-12' />");
                var div_el = $("<div style='font-size: 16px;font-weight: bold; padding-bottom:10px;' />");
                //
                div_el.append(" &raquo; " + item.feature);
                col_el.append(div_el);




                //
                var feature_value_id = null;
                var feature_custom_value = null;


                //
                if (section_data.features.length){
                    //
                    $.each(section_data.features, function(idx3, item_feature){
                        // SI ES EL MISMO FEATURE ID
                        if (item_feature.feature_id === item.id ){

                            // Si tiene value id y value es por que selecciono un radio value
                            if ( item_feature.value_id > 0 && item_feature.feature_value ){
                                feature_value_id = item_feature.value_id;
                            }

                            // Si no tiene value id pero tiene valor es custom input
                            else if ( !item_feature.value_id && item_feature.feature_value ){
                                feature_custom_value = item_feature.feature_value;
                            }

                            // Si no tiene value ni valor es un n/a
                            else if ( !item_feature.value_id && !item_feature.feature_value ){
                                feature_value_id = "n-a";
                            }
                        }
                    });
                }
                //
                else {
                    feature_value_id = "n-a";
                }



                //
                appendCheckboxItem(item.id, "n-a", "n/a", col_el, feature_value_id);

                //
                if (item.values){
                    $.each(item.values, function(idx2, item2){
                        //console.log(idx2, item2);
                        appendCheckboxItem(item.id, item2.id, item2.value, col_el, feature_value_id);
                    })
                }

                //
                appendInputOtherValue(item.id,"custom", "Otro:&nbsp;", col_el, feature_custom_value);

                //
                row_el.append(col_el);
                $("#features_list").append(row_el);

            });

            //
            bindEvents();
        }













        //
        $('#form_features').validate();
        //
        $('#form_features').submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            //
            if ( $('#form_features').valid() ) {
                //
                $('#form_features').ajaxSubmit({
                    url: section_data.opts.endpoint_url + "/" + section_data.id + "/features",
                    beforeSubmit: function(arr){
                        disable_btns();
                        preload(".section-preloader, .overlay", true);
                    },
                    success: function(response){
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        if (response && response.id){
                            //
                            app.Toast.fire({ icon: 'success', title: "Registros Actualizados Correctamente" });
                            //
                            section_data.opts.loadData();
                        }
                        //
                        else if (response.error){
                            app.Toast.fire({ icon: 'error', title: response.error});
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                        }
                    },
                    error: function(response){
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
                    }
                });

            }
        });





        //
        app.loadFeatures = function(){

            //
            disable_btns();
            preload(".section-preloader, .overlay", true);

            //
            $.ajax({
                type:'GET',
                url: app.supadmin_url + "/cat-products-features/dd?tps_id=" + section_data.tipo_producto_servicio_id,
                success:function(response){;
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");

                    //
                    if ( response && response.length ){
                        buildFeaturesList(response);
                    }
                    //
                    else if (response.error){
                        smartalert("Error", "<i class='fa fa-clock-o'></i> <i>"+response.error+"</i>", "#C46A69", "fa fa-times fa-2x fadeInRight animated");
                    }
                },
                error: function(){
                    //
                    enable_btns();
                    preload(".section-preloader, .overlay");
                    //
                    smartalert("Error", "<i class='fa fa-clock-o'></i> <i>Error en el servidor al intentar obtener datos del registro</i>", "#C46A69", "fa fa-times fa-2x fadeInRight animated");
                }
            });

        }




        //
        app.loadFeatures();




    }
    return {init: moduleReady}
});