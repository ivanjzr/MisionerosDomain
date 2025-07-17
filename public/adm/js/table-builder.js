
//
$.buildTable = function(options){

    //
    if (!this.funCall){
        this.funCall = 1;
    } else {
        this.funCall++;
    }
    var _prefix = "_idx" + this.funCall;
    var _logPrefix = "$.buildTable: ";


    //
    if (!options.tableItemsName){
        alert("table name required"); return;
    }

    //
    var defaults = {
        // selectors
        addItemName: ".btnAddItem",
        //
        btnSelectItem: ".btnSelectItem"+_prefix,
        btnSelectCheckboxItem: ".btnSelectCheckboxItem"+_prefix,
        btnDeleteItem: ".btnDeleteItem"+_prefix,
        btnEditItem: ".btnEditItem"+_prefix,
        //
        modalSize: "md",
        //
        data: null,
    }

    //
    var settings = $.extend({}, defaults, options);
    //console.log("------------settings");
    //console.log(settings);

    //
    var ret = {
        items: [],
        //
        bindAddUpdate: function(append_url){
            //
            let self = this;
            //
            var form_name = "#form_section";
            //
            $(form_name).validate();
            //
            $(form_name).submit(function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();

                //
                if ( $(form_name).valid() ) {
                    //
                    var add_url = options.url + "/" + ((append_url) ? append_url : "");
                    //console.log(add_url);
                    //
                    $(form_name).ajaxSubmit({
                        url: add_url,
                        beforeSubmit: function(arr){
                            //
                            disable_btns();
                            preload(".section-preloader, .overlay", true);
                        },
                        success: function(send_response){
                            //
                            enable_btns();
                            preload(".section-preloader, .overlay");
                            //
                            if (send_response && send_response.id){

                                //
                                var str_msg = (append_url) ? "Registro Editado Exitosamente" : "Registro Agregado Exitosamente";
                                app.Toast.fire({ icon: 'success', title: str_msg });
                                //
                                $("#modal-section").find('.modal').modal("hide");

                                //
                                self.loadItems();

                            }
                            //
                            else if (send_response.error){
                                app.Toast.fire({ icon: 'error', title: send_response.error });
                            }
                            //
                            else {
                                app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                            }
                        },
                        error: function(response_error){
                            enable_btns();
                            preload(".section-preloader, .overlay");
                            //
                            app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                        }
                    });
                }

            });
        },

        //
        onLoadReady: function(){
            //console.log("onLoadReady");
            //
            if ($.isFunction(options.loadComplete)){options.loadComplete(this, settings);}
            //
            var self = this;
            //
            $(settings.btnSelectItem).unbind("click")
            $(settings.btnSelectItem).click(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                var info = $(this).data("info")
                //console.log("btn select record: ", info);
                //
                $(".nav-link").removeClass("active");
                $(this).addClass("active");
                //
                var item_el = $(this).parent();
                //
                if ($.isFunction(options.onSelectItem)){options.onSelectItem(self, info, item_el);}
            });


            //
            $(settings.btnSelectCheckboxItem).unbind("click")
            $(settings.btnSelectCheckboxItem).click(function(e){
                //
                var info = $(this).data("info")
                var is_checked = $(this).is(":checked");
                //console.log("btn select record: ", info);
                //
                var item_el = $(this).parent().parent();
                //
                if (is_checked){
                    item_el.children('td').addClass('active');
                } else {
                    item_el.children('td').removeClass('active');
                }
                //
                if ($.isFunction(options.onSelectCheckboxItem)){options.onSelectCheckboxItem(self, info);}
            });


            //
            $(settings.btnEditItem).unbind("click")
            $(settings.btnEditItem).click(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();

                //
                var info = $(this).data("info")
                //console.log("btn edit record: ", info);

                //
                loadModalV2({
                    id: "modal-section",
                    modal_size: settings.modalSize,
                    data: info,
                    html_tmpl_url: options.editModalPath,
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();

                        //
                        var editModalTitle = null;
                        if ($.isFunction(options.editModalTitle)){
                            editModalTitle = options.editModalTitle(info);
                        } else if (options.editModalTitle){
                            editModalTitle = options.editModalTitle;
                        }

                        // modal title
                        $('#modal-title').text(editModalTitle);
                        $('.btnAdd2').html("<i class='fa fa-save'></i> Update");

                        //
                        self.bindAddUpdate(info.id);
                        if ($.isFunction(options.editReadyEvt)){options.editReadyEvt(info);}
                    }
                });
            });



            //
            $(settings.btnDeleteItem).unbind("click")
            $(settings.btnDeleteItem).click(function(e){
                e.preventDefault();

                //
                var info = $(this).data("info");
                //console.log("btn delete: ", info);

                //
                var this_el = $(this);



                //
                if ($.isFunction(options.beforeDeleteEvt)){
                    if ( !options.beforeDeleteEvt(info, this_el) ){
                        return;
                    }
                }

                //
                var deleteConfirmText = null;
                if ($.isFunction(options.deleteConfirmText)){
                    deleteConfirmText = options.deleteConfirmText(info);
                } else if (options.deleteConfirmText){
                    deleteConfirmText = options.deleteConfirmText;
                }

                //
                if (confirm(deleteConfirmText)){

                    //
                    var delFieldName = (options.delFieldName) ? options.delFieldName : "id";
                    //console.log(delFieldName);

                    //
                    preload(".section-preloader, .overlay", true);
                    var url_del = (options.url_del ? options.url_del : "");
                    //
                    post({
                        url: options.url + url_del,
                        data: {
                            [delFieldName]: info[delFieldName]
                        },
                        success: function(send_response){
                            //
                            preload(".section-preloader, .overlay");
                            //
                            if ( send_response && ( send_response.id || send_response.success ) ){
                                //
                                app.Toast.fire({ icon: 'success', title: "Registro eliminado exitosamente" });
                                //
                                if ($.isFunction(options.onAfterDelete)){options.onAfterDelete(self, settings, this_el.parent().parent());}
                            }
                            //
                            else if (send_response.error){
                                app.Toast.fire({ icon: 'error', title: send_response.error });
                            }
                        },
                        error: function(){
                            //
                            preload(".section-preloader, .overlay");
                            app.Toast.fire({ icon: 'error', title: "No se pudo completar la operación. Verifica tu conexión o contacta al administrador." });
                        }
                    });
                }
            });
        },

        //
        addItem: function(idx, item){
            //console.log("adding item index: ", idx);
            var str_item = options.parseItem(idx, settings, item);
            $(settings.tableItemsName).append(str_item);
        },
        loadItems: function(){
            //console.log("loading items");
            var self = this;


            //
            if ($.isFunction(options.onBeforeLoad)){options.onBeforeLoad();}

            //
            if (!options.url){
                console.info(_logPrefix, "No url provided"); return;
            }

            //
            $(settings.tableItemsName).html("");
            self.items = [];
            //
            var url_read = (options.url_read ? options.url_read : "");
            //
            preload(".section-preloader, .overlay", true);
            disable_btns();
            //
            get({
                url: options.url + url_read,
                success: function(response){
                    //
                    preload(".section-preloader, .overlay");
                    enable_btns();
                    //
                    if (response && response.length){
                        //
                        $.each(response, function(idx, item){
                            //
                            self.items.push(item);
                            self.addItem(idx, item);
                        });
                    }
                    //
                    self.onLoadReady();
                }
            });
        },
        //
        onInit: function(){
            //
            var self = this;

            //
            $(settings.addItemName).unbind("click")
            $(settings.addItemName).click(function(e){
                e.preventDefault();
                e.stopImmediatePropagation();
                //
                //console.log("btn add item: ", settings.data);
                //
                loadModalV2({
                    id: "modal-section",
                    modal_size: settings.modalSize,
                    html_tmpl_url: options.addModalPath,
                    onBeforeLoad: function(){
                        disable_btns();
                    },
                    onInit: function(){
                        //
                        enable_btns();


                        // modal title
                        $('#modal-title').text(options.addModalTitle);
                        $('.btnAdd2').html("<i class='fa fa-plus'></i> Add");

                        //
                        self.bindAddUpdate();
                        if ($.isFunction(options.addReadyEvt)){options.addReadyEvt(self, settings);}

                    }
                });

            });

        }
    }

    //
    ret.onInit();
    ret.loadItems();

    //
    return this;
}
