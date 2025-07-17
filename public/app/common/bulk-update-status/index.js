define(function(){
    function moduleReady(modal){


        //
        var filter_status_id = $("#filter_status_id").val();
        //
        loadSelectAjax({
            id: "#status_id",
            url: app.admin_url + "/sale-status/list?excl=cancelled",
            parseFields: function(item){
                return item.status_title;
            },
            saveValue: true,
            prependEmptyOption: true,
            emptyOptionText: "--select new status",
            enable: true,
            focus: true,
            default_value: (filter_status_id) ? filter_status_id : null,
            onChange: function(){
                //
            }
        });




        //
        var template_status_update_id = 9;

        //
        loadSelectAjax({
            id: "#msg_id",
            url: app.admin_url + "/templates/messages/type/" + template_status_update_id,
            parseFields: function(item){
                return item.maqueta_nombre + " (" + item.id + ")";
            },
            saveValue: true,
            prependEmptyOption: true,
            emptyOptionText: "--all",
            enable: true,
            focus: true,
            onChange: function(){
                setMsgId();
            },
            onReady: function(){
                setMsgId();
            }
        });


        //
        function setMsgId(){
            var msg_info = $("#msg_id").find(':selected').data('info');
            if (msg_info && msg_info.id){
                $("#msg").val(msg_info.sms_msg);
            } else {
                $("#msg").val("");
            }
        }




        //
        $("#notify_customer").click(function(e) {

            //
            var notify_customer = $(this).is(":checked");

            //
            if (notify_customer){
                $(".notify_customer_container").show();
            } else {
                $(".notify_customer_container").hide();
            }

        });







        //
        $("#form_section").validate();
        //
        $("#form_section").submit(function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();


            //
            var send_to = $('input[name=send_to]:checked').val();
            //console.log(send_to);
            if (!send_to){
                alert("Select send to"); return;
            }

            //
            if ( $("#form_section").valid() ) {
                //
                $("#form_section").ajaxSubmit({
                    url: app.admin_url + "/sales/bulk-update-status",
                    beforeSubmit: function(arr){
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay", true);


                        //
                        var this_date = $('#event_date').datetimepicker('date');
                        var filter_start_date = moment(this_date).format("YYYY-MM-DD")
                        //
                        arr.push({
                            "name": "start_date",
                            "value": filter_start_date
                        });


                        //
                        if (send_to === "selected"){
                            //
                            var arr_checked_ids = dataTablesGetCheckedIds('#grid_section');
                            //
                            arr.push({
                                "name": "arr_selected_ids",
                                "value": arr_checked_ids
                            });
                        }

                    },
                    success: function(send_response){
                        //
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        if (send_response && send_response.id){

                            //
                            app.Toast.fire({ icon: 'success', title: "Status updated" });
                            //$("#modal-update-status").find('.modal').modal("hide");
                            //
                            $("#grid_section").DataTable().ajax.reload();

                        }
                        //
                        else if (send_response.error){
                            app.Toast.fire({ icon: 'error', title: send_response.error });
                        }
                        //
                        else {
                            app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                        }
                    },
                    error: function(response_error){
                        enable_btns();
                        preload(".section-preloader, .overlay");
                        //
                        app.Toast.fire({ icon: 'error', title: "The operation could not be completed. Check your connection or contact the administrator." });
                    }
                });
            }
        });




    }
    return {init: moduleReady}
});






