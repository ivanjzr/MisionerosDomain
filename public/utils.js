
/*
 * Disable/enable btns
 * */
function disable_btns(){
    //
    $('.btn')
        .attr("disabled", true)
        .addClass("disabled");
    $('button')
        .attr('disabled', true)
        .addClass('disabled');
}
function enable_btns(){
    //
    $('.btn')
        .removeAttr("disabled")
        .removeClass("disabled");
    $('button')
        .removeAttr("disabled")
        .removeClass('disabled');
}


//
function preload(section_id, preload){
    if (preload){
        $(section_id).show();
    } else {
        $(section_id).hide();
    }
}

function getPreloader(the_msg){
    return "<div class='text-center preloader-item'><img src='/sites/qponea/images/loading.gif' style='width:25px;' /> " + the_msg + " </div>";
}

function preloadV2(container_id, msg){
    if (msg){
        var loading = getPreloader(msg);
        $(container_id).html(loading).show();
    }  else {
        $(container_id).html("").hide();
    }
}


function GetUniqTime(prefix){
    var d = new Date();
    var prefix = prefix || '';
    return prefix+d.getTime();// return date for dynamic element
}


function dynurl(){
    return '?_='+GetUniqTime();// return date for dynamic element
}

//
function loadSelectAjax(options){

    //
    if (!$(options.id).length){
        console.info("unable to loadSelectAjax: Element " + options.id + " Does Not Exists");
        return;
    }

    //
    $(options.id).empty();
    // local storage prefix value
    var ls_prefix = (options.lsPrefix) ? options.lsPrefix : "_val";
    //
    if (options.url){

        //
        var extended_options = {
            data: ((options.data) ? options.data : {}),
            contentType: ((options.contentType) ? options.contentType : "")
        }


        //
        var ls_value = null;
        var selected_value = null;
        //
        if( options.saveValue ){
            ls_value = localStorage.getItem(options.id + ls_prefix);
        }
        //console.log("ls value", ls_value);

        //
        var first_id = null;



        //
        $.ajax($.extend(extended_options, {
            type: ((options.type) ? options.type : "GET"),
            url: options.url,
            beforeSend: function (xhr) {
                if (options.authToken){xhr.setRequestHeader ("Authorization", options.authToken);}
                if (options.Utype){xhr.setRequestHeader ("Utype", options.Utype);}
            },
            success:function(response){
                //
                if (response.error){
                    alert(response.error); return;
                }
                //
                var the_results = null;

                //
                if (options.parseResults){
                    the_results = options.parseResults(response);
                } else {
                    the_results = response;
                }

                //
                if ( the_results && $.isArray(the_results) && the_results.length){

                    //
                    let cant_records = the_results.length;

                    //
                    $.each(the_results, function(idx, item){
                        //console.log(item);
                        //
                        var id_field_name = ( options.id_field_name ) ? options.id_field_name : "id";
                        var text_field_name = ( options.parseFields && $.isFunction(options.parseFields) ) ? options.parseFields(item, idx) : item["name"];
                        //
                        var sel_option = $("<option />")
                            .val(item[id_field_name])
                            .text(text_field_name);
                        //
                        if (!options.noJsonData){
                            sel_option.attr("data-info", JSON.stringify(item));
                        }


                        //
                        sel_option.attr("data-index", idx);

                        //
                        if (idx===0){
                            first_id = item[id_field_name];
                        }

                        //
                        if ( options.selectIfField && item[options.selectIfField] && item[options.selectIfField] == 1){
                            sel_option.prop("selected", 'selected');
                        }

                        // SI SE ENCUENTRA EL VALOR EN LA LISTA ENTONCES LO PASAMOS
                        //console.log("iter val: ", ls_value, item[id_field_name]);
                        if( options.saveValue && parseInt(ls_value) === parseInt(item[id_field_name]) ){
                            selected_value = ls_value;
                            //console.log("se encontro en: ", ls_value, item[id_field_name]);
                        }


                        //
                        $(options.id).append(sel_option);
                    });
                }

                // if error occurs
                if ( the_results && the_results.error ){
                    alert(response.error);
                }


                // #1 hacer el prepend si se solicito
                if (options.emptyOptionText){
                    $(options.id).prepend($("<option />").val("").text(options.emptyOptionText));
                }

                /* si esta "preventClear" presente no muestra resetea el valor */
                if (!options.preventClear){
                    $(options.id).val("");
                }


                // #2 establecer el default value (hace override del prepend)
                if ( options.default_value ){
                    $(options.id).val(options.default_value);
                }
                //
                else if (options.selectFirstOption){
                    $(options.id).val($(options.id + " option:first").val());
                }
                //
                else if (options.selectLastOption){
                    $(options.id).val($(options.id + " option:last").val());
                }
                // establece el default value del local storage
                else if( options.saveValue && selected_value ){
                    $(options.id).val(selected_value);
                }

                // habilita & focus al final
                if (options.enable){
                    $(options.id).removeAttr("disabled");
                }
                //
                if (options.focus){
                    $(options.id).focus();
                }

                //
                if( options.onReady && $.isFunction(options.onReady)){
                    options.onReady($(options.id).val(), the_results);
                }

                //
                $(options.id).unbind("change").bind("change", function(){
                    //
                    if( options.saveValue ){
                        //console.log("set value: ", $(options.id).val(), " for: ", options.id + ls_prefix);
                        localStorage.setItem(options.id + ls_prefix, $(options.id).val());
                    }
                    //
                    if( options.onChange && $.isFunction(options.onChange)){
                        options.onChange($(options.id).val());
                    }
                });


            },
            error: function(){
                alert("Error en el servidor al intentar obtener los registros");
            }
        }));
    } else {


        // #1 hacer el prepend si se solicito
        if (options.prependEmptyOption){
            //
            var emptyOptionText = "--select";
            if (options.emptyOptionText){
                emptyOptionText = options.emptyOptionText;
            }
            $(options.id).prepend($("<option />").val("").text(emptyOptionText));
        }

        /* si esta "preventClear" presente no muestra resetea el valor */
        if (!options.preventClear){
            $(options.id).val("");
        }


        // #2 establecer el default value (hace override del prepend)
        if ( options.default_value ){
            $(options.id).val(options.default_value);
        }

        // habilita & focus al final
        if (options.enable){
            $(options.id).removeAttr("disabled");
        }
        //
        if (options.focus){
            $(options.id).focus();
        }

        //
        if( options.onReady && $.isFunction(options.onReady)){
            options.onReady($(options.id).val());
        }

        //
        if( options.onChange && $.isFunction(options.onChange)){
            $(options.id).unbind("change").bind("change", options.onChange);
        }

    }
}



/**
 * selectLoad2 - Versión mejorada para cargar options en select via AJAX
 * @param {Object} options - Configuración
 * @returns {Object} - Instancia con método reload()
 */
/**
 * selectLoad2 - Versión mejorada para cargar options en select via AJAX o datos pre-cargados
 * @param {Object} options - Configuración
 * @returns {Object} - Instancia con método reload() 
 */
function selectLoad2(options) {
    const instance = {
        options: options,
        $select: null,
        lsPrefix: options.lsPrefix || "_val",
        lastResults: null,
        _isLoading: false, // Flag interno para prevenir dobles cargas
        
        init() {
            this.$select = $(options.id);
            if (!this.$select.length) {
                console.warn(`selectLoad2: Element ${options.id} not found`);
                return this;
            }
            this.load();
            return this;
        },
        
        async load() {
            // Prevenir cargas múltiples simultáneas
            if (this._isLoading) return;
            this._isLoading = true;
            
            this.$select.empty();
            
            // NUEVA FUNCIONALIDAD: Verificar si se pasaron datos directamente
            if (options.data && Array.isArray(options.data)) {
                this.processResults(options.data);
            } else if (options.url) {
                await this.loadFromAjax();
            } else {
                this.setupLocalOptions();
            }
            
            this.setupEvents();
            this.finalSetup(); // Solo una llamada a finalSetup desde aquí
            
            this._isLoading = false; // Reset del flag al terminar
        },
        
        async loadFromAjax() {
            try {
                const response = await $.ajax({
                    type: options.type || "GET",
                    url: options.url,
                    data: options.ajaxData || {}, // Cambiado de 'data' a 'ajaxData' para evitar conflicto
                    contentType: options.contentType || "",
                    beforeSend: (xhr) => {
                        if (options.authToken) xhr.setRequestHeader("Authorization", options.authToken);
                        if (options.Utype) xhr.setRequestHeader("Utype", options.Utype);
                    }
                });
                
                if (response.error) {
                    alert(response.error);
                    return;
                }
                
                this.processResults(response);
                
            } catch (error) {
                console.error('selectLoad2 AJAX Error:', error);
                alert("Error en el servidor al intentar obtener los registros");
            }
        },
        
        processResults(response) {
            const results = options.parseResults ? options.parseResults(response) : response;
            this.lastResults = results; // Guardar para uso posterior
            
            // Siempre agregar empty option si se especifica
            if (options.emptyOptionText) {
                this.$select.prepend($('<option/>').val("").text(options.emptyOptionText));
            }
            
            if (!results || !Array.isArray(results) || !results.length) {
                if (results?.error) alert(results.error);
                
                // NUEVO: Manejo de texto cuando no hay registros
                if (options.nameOnNoRecords) {
                    this.$select.empty();
                    this.$select.append($('<option/>').val("").text(options.nameOnNoRecords));
                }
                
                this.finalSetup();
                return;
            }
            
            const savedValue = options.saveValue ? localStorage.getItem(options.id + this.lsPrefix) : null;
            let selectedValue = null;
            let firstId = null;
            
            results.forEach((item, idx) => {
                const idField = options.id_field_name || "id";
                const textField = options.parseFields && typeof options.parseFields === 'function' 
                    ? options.parseFields(item, idx) 
                    : item.name;
                
                const $option = $('<option/>')
                    .val(item[idField])
                    .text(textField)
                    .attr('data-index', idx);
                
                if (!options.noJsonData) {
                    $option.attr('data-info', JSON.stringify(item));
                }
                
                if (idx === 0) firstId = item[idField];
                
                if (options.selectIfField && item[options.selectIfField] == 1) {
                    $option.prop('selected', true);
                }
                
                if (options.saveValue && savedValue == item[idField]) {
                    selectedValue = savedValue;
                }
                
                // NUEVA FUNCIONALIDAD: Callback durante iteración
                if (options.onItemIteration && typeof options.onItemIteration === 'function') {
                    options.onItemIteration(item, $option, idx, this.$select);
                }
                
                this.$select.append($option);
            });

            //
            if (selectedValue){
                this.setDefaultValue(selectedValue, firstId);
            } else {
                this.setDefaultValue("", firstId);
            }

        },
        
        setupLocalOptions() {
            if (options.emptyOptionText) {
                this.$select.prepend($('<option/>').val("").text(options.emptyOptionText));
            } else if (options.prependEmptyOption) {
                const emptyText = "--select";
                this.$select.prepend($('<option/>').val("").text(emptyText));
            }
            
            // NO llamar finalizeAfterLoad aquí - se llama desde load()
        },
        
        setDefaultValue(savedValue, firstId) {
            // Clear value unless prevented
            if (!options.preventClear) {
                this.$select.val("");
            }
            
            // Set default value (priority order)
            if (options.default_value) {
                this.$select.val(options.default_value);
            } else if (options.selectFirstOption) {
                this.$select.val(this.$select.find('option:first').val());
            } else if (options.selectLastOption) {
                this.$select.val(this.$select.find('option:last').val());
            } else if (options.saveValue && savedValue) {
                this.$select.val(savedValue);
            } else {
                this.$select.val("");
            }
            
            // NO llamar finalizeAfterLoad aquí - se llama desde load()
        },
        
        // Eliminar este método que causaba doble llamada
        // finalizeAfterLoad() {
        //     this.finalSetup();
        // },
        
        setupEvents() {
            this.$select.off('change.selectLoad2').on('change.selectLoad2', () => {
                const currentValue = this.$select.val();
                
                if (options.saveValue) {
                    localStorage.setItem(options.id + this.lsPrefix, currentValue);
                }
                
                if (options.onChange && typeof options.onChange === 'function') {
                    options.onChange(currentValue);
                }
            });
        },
        
        finalSetup() {
            if (options.enable) {
                this.$select.removeAttr('disabled');
            }
            
            if (options.focus) {
                this.$select.focus();
            }
            
            if (options.onReady && typeof options.onReady === 'function') {
                options.onReady(this.$select.val(), this.lastResults);
            }
        },
        
        // Método público para recargar
        reload() {
            console.log('Reloading selectLoad2:', options.id);
            this._isLoading = false; // Reset manual del flag
            this.load();
            return this;
        },
        
        // NUEVO: Método para actualizar con nuevos datos
        updateData(newData) {
            this.options.data = newData;
            this.load();
            return this;
        },
        
        // Método para destruir la instancia
        destroy() {
            this.$select?.off('change.selectLoad2');
            return this;
        }
    };
    
    return instance.init();
}

// Ejemplo de uso:
/*
const mySelect = selectLoad2({
    id: '#mi-select',
    url: '/api/opciones',
    saveValue: true,
    emptyOptionText: 'Selecciona una opción',
    onChange: function(value) {
        console.log('Valor seleccionado:', value);
    }
});

// Para recargar externamente:
mySelect.reload();
*/





/*

// LOAD RADIO AJAX EXAMPLE
loadRadioAjax({
    url:app.admin_url + "/sys/plans-types",
    radioName: "plan_type_id",
    fieldName: "type",
    containerId: "#plans_types_container",
    default_checked_index: 0
});
 */
function loadRadioAjax(opts){
    //
    disable_btns();
    //
    get({
        url: opts.url,
        success: function(response){
            //
            enable_btns();
            //
            if (response && response.length){

                //
                if (opts.radAll){
                    var strCheckedAll = "";
                    if (opts.default_checked_all){
                        strCheckedAll = "checked";
                    }
                    var str_el2 = "<div class='col-lg-2 col-md-2 col-sm-12 col-12'>";
                    str_el2 += "<label><input type='radio' class='styled-checkbox' name='"+opts.radioName+"' value='' "+strCheckedAll+"> " + opts.radAll + " </label>";
                    str_el2 += "</div>";
                    $(opts.containerId).append(str_el2);
                }

                var n = 3;

                //
                $.each(response, function(idx, item){
                    //console.log(idx, item);
                    //
                    var str_checked = "";
                    //
                    if ( opts.default_value ){
                        if ( opts.default_value === item.id ){
                            str_checked = "checked";
                        }
                    }
                    //
                    else {
                        if ( $.isNumeric(opts.default_checked_index) && opts.default_checked_index === idx ){
                            str_checked = "checked";
                        }
                    }



                    //
                    var str_el = "";
                    //
                    if( idx%n === 0){
                    }

                    //
                    str_el += "<div class='col-lg-2 col-md-2 col-sm-12 col-12'>";
                    str_el += "<label><input type='radio' class='styled-checkbox' name='"+opts.radioName+"' value='" + item.id + "' "+str_checked+"> " + item[opts.fieldName] + " </label>";
                    str_el += "</div>";

                    //
                    if( idx%n === 0){
                    }

                    //
                    $(opts.containerId).append(str_el);
                });

            }
            //
            if (response && response.error){
                app.Toast.fire({ icon: 'error', title: send_response.error });
            }

            //
            if ($.isFunction(opts.onReady)){opts.onReady();}

            //
            $("input[name="+opts.radioName+"]").click(function(){
                if ($.isFunction(opts.onSelect)){opts.onSelect();}

            });
        },
        error: function(){
            app.Toast.fire({ icon: 'error', title: "Error en el servidor o internet inactivo" });
        }
    });
}





//
function getBrowserUtils(){
    //
    var device_lang = navigator.language || navigator.userLanguage;
    //
    var spltd_lang = device_lang.split("-");
    var userLang = (spltd_lang && spltd_lang[0]) ? spltd_lang[0] : spltd_lang;
    //console.log("userLang: ", userLang);
    //
    var selected_lang = ( userLang === 'es' ) ? 379 : 467;
    var currency_type = ( userLang === 'es' ) ? "mxn" : "usd";
    //console.log(userLang, selected_lang);
    /*
    var elem_user_lang = $(".user_lang");
    if (elem_user_lang.length){
        elem_user_lang.text(device_lang + " / " + userLang + " / " + selected_lang);
    }
     */
    //
    return {
        userLang: userLang,
        selected_lang: selected_lang,
        currency_type: currency_type
    }
}


function isMobileSize(cb){
    //
    if ($(window).width() < 450) {
        cb();
    }
}




function allStorage(){
    var values = [],
        keys = Object.keys(localStorage),
        i = keys.length;

    while ( i-- ) {
        values.push( localStorage.getItem(keys[i]) );
    }
    return values;
}


function get(opts){
    //
    $.ajax({
        type: "GET",
        url: opts.url,
        async: (opts.async) ? opts.async : true,
        beforeSend: function (xhr) {
            if (opts.authToken){xhr.setRequestHeader ("Authorization", opts.authToken);}
            if (opts.Utype){xhr.setRequestHeader ("Utype", opts.Utype);}
        },
        success: function (response) {
            opts.success(response);
        },
        error: function () {
            if ($.isFunction(opts.error)){opts.error();}
        }
    });
}


function post(opts){
    //
    disable_btns();
    //
    $.ajax({
        type: "POST",
        url: opts.url,
        data: $.param(opts.data),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        beforeSend: function (xhr) {
            if (opts.authToken){xhr.setRequestHeader ("Authorization", opts.authToken);}
            if (opts.Utype){xhr.setRequestHeader ("Utype", opts.Utype);}
            //
            if (opts.beforeSend && $.isFunction(opts.beforeSend)){opts.beforeSend(xhr);}
        },
        success: function (response) {
            enable_btns();
            if (opts.success && $.isFunction(opts.success)){opts.success(response);}
        },
        error: function () {
            enable_btns();
            if ( opts.error && $.isFunction(opts.error) ){
                opts.error();
            } else {
                alert("Network error or no internet connection");
            }
        }
    })
}


function ifVal(obj, name){
    if (obj[name]){
        return obj[name];
    }
    //
    return "";
}


// get safe null values
function safeNulValue(field_value, custom_val){
    if (field_value){
        return field_value;
    }
    //
    return (custom_val) ? custom_val : "";
}

function ifValueFound(field_value, compare_to, custom_val, not_found_val){
    if ( field_value === compare_to ){
        return custom_val;
    }
    return not_found_val;
}

function fmtAmount(amount){
    //
    if ($.isNumeric(amount)){
        //return "$" + parseFloat(amount).toFixed(2).toString();
        return "$" + parseFloat(amount).toLocaleString('en-US', {maximumFractionDigits:2});
    }
    //
    return "";
}


function fmtAmount2(amount){
    //
    if ($.isNumeric(amount)){
        return "$" + parseFloat(amount).toLocaleString('en-US', {maximumFractionDigits:2});
    }
    //
    return "$0";
}


function fmtAmount3(amount){
    //
    if ($.isNumeric(amount)){
        return "$" + parseFloat(amount).toFixed(2).toString();
    }
    //
    return "$0";
}

/*
* quita el "#" y el "." de un elemento
* */
function noid(str_name){
    return str_name.replace(/[#.]/g,'');
}





function getElemData(ctx, field_name){
    //
    if (typeof $(ctx).data(field_name) !== 'undefined') {
        return $(ctx).data(field_name);
    }
    return "";
}


//
function getAuthUser(){
    //
    var auth_user = localStorage.getItem("auth_user");
    auth_user = (auth_user) ? JSON.parse(auth_user) : null;
    return auth_user;
}

//
function badgeText(badge_type, text){
    return "<span class='badge "+badge_type+"'>"+text+"</span>"
}

//
function qs(name, value){
    return (value) ? "&"+name+"="+value : "";
}


//
function appendToSummernote(elementId, html){
    $(elementId).summernote("code", $(elementId).summernote("code") + html);
    $(elementId).summernote({focus: true});
}


//
getLSItem = function(item_name){
    //
    var item = localStorage.getItem(item_name), the_item;
    try{
        the_item = JSON.parse(item);
    } catch(ex){
        the_item = null;
    }
    return the_item;
}



$.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)')
        .exec(window.location.search);

    return (results !== null) ? results[1] || 0 : false;
}


$.getUrlParameter = function(sParam, page_url) {
    //
    if (!page_url){
        page_url = window.location.search.substring(1);
    }
    //
    var sURLVariables = page_url.split('&'),
        sParameterName,
        i;
    //
    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return typeof sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }
    //
    return false;
};


function removeQsValue(arr_names){
    if (arr_names && arr_names.length){
        const currentUrl = URI(window.location.href);
        $.each(arr_names, function(idx, val){
            console.log(idx, val);
            currentUrl.removeQuery(val);
        });
        window.history.replaceState({}, document.title, currentUrl.toString());
    }

}



function showTab(tab_title_id, tab_id){
    //
    $(".nav-link").removeClass("active");
    $(tab_title_id).addClass('active');
    //
    $(".tab-pane").removeClass("show active");
    $(tab_id).tab('show');
}


function loadModule(options){

    //
    if (options.beforeLoad){
        options.beforeLoad();
    }

    //
    if (options.container_id){
        $(options.container_id).html("");
    }

    //
    if (options.html_url){

        // load html template
        $.get(options.html_url, function (html) {

            //
            if (options.container_id){
                $(options.container_id).html(html);
            }

            //
            if (options.js_url){
                //
                requirejs([options.js_url], function (module) {
                    //
                    module.init((options.data) ? options.data : null);
                    //
                    if ( options.onInit && $.isFunction(options.onInit)){
                        options.onInit();
                    }
                });
            } else {
                options.onReady(html);
            }
        });

    }

    //
    else if (options.js_url) {
        //
        requirejs([options.js_url], function (module) {
            //
            module.init((options.data) ? options.data : null);
            //
            if ( options.onInit && $.isFunction(options.onInit)){
                options.onInit();
            }
        });
    }

    //
    else {
        options.onReady();
    }
}


$.fn.int = function(){
    return parseInt($(this).val())
}



function setCheckbox(obj_id, str_name){
    return "<input type='checkbox' class='styled-checkbox' id='"+obj_id+"'>";
}

function fmtActiveV2(field_value, display_inactive){
    if (field_value){
        return "<span class='badge badge-success'>Active</span>";
    } else if (display_inactive) {
        return "<span class='badge badge-danger'>Inactive</span>";
    }
    return "";
}


function fmtStatusWithText(field_value, active_text, inactive_text){
    if (field_value){
        return "<span class='badge badge-success'>"+active_text+"</span>";
    } else {
        return "<span class='badge badge-danger'>"+inactive_text+"</span>";
    }
}

function fmtActive(field_value, display_inactive) {
    if (field_value) {
        return `<span class="text-success" title="Activo">
            <i class="fas fa-check-circle"></i>
        </span>`;
    } else if (display_inactive) {
        return `<span class="text-danger" title="Inactivo">
            <i class="fas fa-times-circle"></i>
        </span>`;
    }
    return "";
}

function fmtActiveV2(field_value, display_inactive) {
    if (field_value) {
        return `<span class="badge bg-success">
            <i class="fas fa-check me-1"></i>Activo
        </span>`;
    } else if (display_inactive) {
        return `<span class="badge bg-danger">
            <i class="fas fa-times me-1"></i>Inactivo
        </span>`;
    }
    return "";
}



function fmtActive3(field_value){
    if (field_value){
        return "<i class='fas fa-check' style='color:green;'></i>";
    }
    return "";
}


function fmtDateSpanish(field_value, inc_time){
    var dt = moment(field_value);
    if (field_value && dt){
        if (inc_time){
            return dt.format("DD MMM YYYY - h:mm A");
        } else {
            return dt.format("DD MMMM YYYY");
        }
    }
    return "";
}


function fmtTimeStamp(datetime){
    return moment(datetime).format("YYYY-MM-DD/HH:mm");
}


function convertirDistancia(metros) {
    const millas = metros * 0.000621371192; // Conversión de metros a millas
    if (millas < 1) {
        return metros + " metros";
    } else {
        return millas.toFixed(2) + " millas";
    }
}


function convertirDuracion(minutos) {
    if (minutos < 60) {
        return minutos + " minutos";
    } else {
        minutos = Number(minutos);
        var d = Math.floor(minutos / (60 * 24));
        var h = Math.floor((minutos % (60 * 24)) / 60);
        var m = Math.round(minutos % 60);

        var dDisplay = d > 0 ? d + (d === 1 ? " día, " : " días, ") : "";
        var hDisplay = h > 0 ? h + (h === 1 ? " hora, " : " horas, ") : "";
        var mDisplay = m > 0 ? m + (m === 1 ? " minuto" : " minutos") : "";

        var result = dDisplay + hDisplay + mDisplay;

        // Eliminar la coma y el espacio al final del resultado
        return result.replace(/,\s*$/, "");
    }
}


/*
// Formatear la hora en formato de 12 horas (AM/PM)
let hora12hrs = moment(this_date).format("YYYY-MM-DD h:mm A");

// Formatear la hora en formato de 24 horas
let hora24hrs = moment(this_date).format("YYYY-MM-DD HH:mm");
 */


function momentFormat(datetime, format){
    return moment(datetime).format(format);
}

function fmtDateEng(field_value, inc_time){
    var dt = moment(field_value);
    if (field_value && dt){
        if (inc_time){
            return dt.format("MMM DD YY - h:mm A");
        } else {
            return dt.format("MMM DD YY");
        }
    }
    return "";
}

function fmtDateEsp(field_value, inc_time){
    var dt = moment(field_value);
    if (field_value && dt){
        if (inc_time){
            return dt.format("DD MMM YY - h:mm A");
        } else {
            return dt.format("DD MMM YY");
        }
    }
    return "";
}


/*
*
* Facilita el uso del Datepicker del TempusDominus
* Almacena en LocalStorage su valor para uso posterior
* */
$.fn.datePickerTask = function(options){
    //
    var moment_saving_format = "YYYY-MM-DD h:mm A";
    //
    var self = this;
    //
    if (options.storeId && options.saveValue){
        //
        var ses_date = getLSItem(options.storeId);
        //console.log(ses_date);
        if (ses_date){
            options.opts.defaultDate = moment(ses_date, moment_saving_format, true);
        }
    }
    //
    $(self).datetimepicker(options.opts);
    //
    $(self).on('change.datetimepicker', function() {
        //
        var this_date = $(this).datetimepicker('date');
        var this_date_obj = moment(this_date);
        //
        if (options.storeId){
            localStorage.setItem(options.storeId, JSON.stringify(this_date_obj.format(moment_saving_format)));
            //console.log(this_date_obj.format("YYYY-MM-DD h:mm A"));
        }
        //
        options.onChange(this_date_obj)
    });

}


//
function addAlert(type, msg, container_id, dismiss_seconds){
    var the_container_id = (container_id) ? container_id : "#alerts-container";
    //
    var alert_elem = $("<div class='alert alert-"+type+"' role='alert'> " + msg + " </div>")
    alert_elem.hide();
    //
    $(the_container_id).append(alert_elem);
    alert_elem.fadeIn("slow");
    //
    if (dismiss_seconds){
        setTimeout(function(){
            //
            $(alert_elem).fadeOut();
        }, dismiss_seconds * 1000);
    }
}

/*
    * Solo si el user no esta activo (registro inicial) nos traemos el estado
    * */
function viewUserState(msg){
    if ( app.auth_user && app.auth_user.token && !app.auth_user.active ){
        //
        get({
            url: app.api_url + "/auth-cust/cust-state",
            authToken: app.auth_user.token,
            success: function(response){
                //
                if ( !(response && response.id && response.active) ){
                    //
                    addAlert("warning", msg);
                }
            }
        });
    }
}




parsePlanMeals = function(arr_items, exclude_size_info){
    //
    var str_vals = "<ul>";
    //
    $.each(arr_items, function(idx, item){
        //
        var str_size_info  = "<span style='color: orangered;'>" + item.meal_size + "</span>";
        if (exclude_size_info){
            str_size_info  = "";
        }
        //
        str_vals += "<li> <small style='font-weight: bold;'>(x" + item.qty + ")</small> " + item.item_info + str_size_info + "</li>";
    });
    //
    str_vals += "</ul>";
    //
    return str_vals;
}

//
parseSaleItems = function(obj, display_ready_o_start_datetime){
    //
    var str_vals = "";

    //
    $.each(obj.sale_items, function(idx, item){



        //var str_prep_date = " " + " Date: " + fmtDateEng(item.ready_o_start_datetime.date);


        //
        if ( item.tipo === 'meals' ){
            var item_meal_size = (item.meal_size) ? " <span style='color:orangered;'>" + item.meal_size + "</span>" : "";
            str_vals += "<div style=''>• <strong>(x" + parseInt(item.qty) + ")</strong> " + item.item_info + item_meal_size + "</div>";
        }

        //
        else if ( item.tipo === 'meal_plans' ){
            str_vals += "<div style='font-weight: bold;'>• (x" + parseInt(item.qty) + ") " + item.item_info + " <span style='color:orangered;'>" + item.meal_size + "/" + item.plan_type + "</span>&nbsp;<small style='color:gray;'>(" + item.meals_qty + "&nbsp;meals)</small></div>";
            str_vals += parsePlanMeals(item.meal_plans);
        }

        //
        else if ( item.tipo === 'subscriptions' ){
            //
            str_vals += "<div style='font-weight:bold'>• (x" + parseInt(item.qty) + ") " + item.item_info + "</div>";
            //
            $.each(item.weeks_plans, function(idx2, week_plan){
                str_vals += "<div style='font-weight: bold;'>• <span style='display: inline-block;border: 1px solid #ff4703;padding: 5px;'>Week #" + week_plan.week_number + "</span> " + week_plan.item_info + " <span style='color:orangered;'>" + week_plan.meal_size + " / " + week_plan.plan_type + "</span>&nbsp;<small style='color:gray;'>(" + week_plan.meals_qty + "&nbsp;meals " + fmtDateEng(week_plan.ready_o_start_datetime.date) + ")</small></div>";
                str_vals += parsePlanMeals(week_plan.meal_plans, true);
            });
        }




    });

    //
    str_vals += "";
    return str_vals
}



function dataTablesGetCheckedIds(grid_id){
    var table = $(grid_id).DataTable();
    //
    var arr_checked_ids = [];
    table.rows().every( function ( rowIdx, tableLoop, rowLoop ) {
        var data = this.data();
        //
        if ($("#"+data.id).is(":checked")){
            arr_checked_ids.push(data.id);
        }
    });
    //console.log(arr_checked_ids);
    return arr_checked_ids;
}




//
dataGrid = function(opts){
    //console.log(opts);
    //
    var dt_obj = {
        // https://datatables.net/reference/option/columns
        columns: opts.columns,
        // https://datatables.net/reference/option/columnDefs
        columnDefs: (opts.columnDefs) ? opts.columnDefs : [],
        responsive: true,
        order: opts.order,
        lengthChange: true,
        autoWidth: false,
        processing: true,
        buttons: ["excel", "pdf", "print", "colvis"],
        //language : app.dt_lang,
        displayStart: 0, /* start 0 = inicia en pagina 1 */
    }

    //
    if (opts.lengthMenu && opts.lengthMenu.length){
        dt_obj.lengthMenu = opts.lengthMenu;
    } else {
        // dt_obj.lengthMenu = [[10,25,50,100,-1],[10,25,50,100, "All"]];
        dt_obj.lengthMenu = [[10,25,50,100],[10,25,50,100]];
    }
    //
    if (opts.pageLength && opts.pageLength > 0 ){
        dt_obj.pageLength = opts.pageLength;
    } else {
        dt_obj.pageLength = 10;
    }


    //
    if (opts.data){
        dt_obj.data = opts.data;
    } else {
        dt_obj.serverSide = true;
        dt_obj.ajax = {
            url: opts.url,
            type: 'GET',
            beforeSend: function (xhr) {
                if (opts.authToken){xhr.setRequestHeader ("Authorization", opts.authToken);}
                if (opts.Utype){xhr.setRequestHeader ("Utype", opts.Utype);}
            },
            data: function(d){
                return d;
            }
        }
    }


    //
    var str_tbl = ' rt ';
    var str_bottom = '<"bottom" <"row" <"col-md-4"i><"col-md-4"l><"col-md-4"p>> >';
    //
    if (opts.hdrBtnsSearch){
        dt_obj.dom = '<"top" <"row" <"col-md-6"B><"col-md-6 text-right"f>> > ' + str_tbl + str_bottom;
    }
    else if (opts.hdrBtns){
        dt_obj.dom = '<"top" <"row" <"col-md-12"B>> > ' + str_tbl + str_bottom;
    }
    else if (opts.hdrSearch){
        dt_obj.dom = '<"top" <"row" <"col-md-12 text-right"f>> > ' + str_tbl + str_bottom;
    }
    else if (opts.cleanTbl){
        dt_obj.dom = '';
    }
    else {
        dt_obj.dom = 'rt ' + str_bottom;
    }
    //
    if (opts.deferLoading){
        dt_obj.deferLoading = 0;
    }


    // expandir
    if (opts.btnExpandir){
        dt_obj.buttons.push({
            text: 'Expandir',
            action: function ( e, dt, node, config ) {
                grid_table.rows(':not(.parent)').nodes().to$().find('td:first-child').trigger('click');
            }
        });
    }
    // colapsar
    if (opts.btnColapsar){
        dt_obj.buttons.push({
            text: 'Colapsar',
            action: function ( e, dt, node, config ) {
                grid_table.rows('.parent').nodes().to$().find('td:first-child').trigger('click');
            }
        });
    }

    //
    if (opts.buttons && opts.buttons.length){
        $.each(opts.buttons, function(idx, new_btn){
            dt_obj.buttons.push(new_btn);
        });
    }


    /*
    * FIX: CREA SOLO CUANDO NO HA SIDO INICIALIZADA
    * */
    if (!$.fn.dataTable.isDataTable(opts.gridId)){

        //
        var grid_table = $(opts.gridId).DataTable(dt_obj);


        //
        if (opts.data){
            //console.log("local data ready");
            setTimeout(function(){
                opts.gridReady(grid_table);
            }, 1000);
        }


        // ON DRAW READY
        grid_table.on( 'draw.dt', function () {
            //console.log("draw.dt");
            if( opts.gridReady && $.isFunction(opts.gridReady)){
                // FIX: for subtables append table classes
                $(opts.gridId).addClass("table table-bordered table-striped")
                //
                opts.gridReady(grid_table);
            }
        });

        // ON RESPONSIVE DISPLAY READY
        grid_table.on( 'responsive-display.dt', function ( e, datatable, row, showHide, update ) {
            //console.log( 'Details for row '+row.index()+' '+(showHide ? 'shown' : 'hidden') );
            //console.log("responsive-display.dt");
            if( opts.gridReady && $.isFunction(opts.gridReady)){
                opts.gridReady(grid_table);
            }
        });

        if( opts.dataReady && $.isFunction(opts.dataReady)){
            grid_table.on('xhr.dt', function (e, settings, json, xhr) {
                opts.dataReady(json);
            });
        }
        


        //
        return grid_table;
    }
}



function getDataTableData(grid_id){
    var data = $(grid_id).DataTable().rows().data();
    if (data){
        return data.toArray();
    }
    return null;
}


function hasNumber(myString) {
    return /\d/.test(myString);
}



//
function getMiles(i){
    return i*0.000621371192;
}



function ucFirst(str){
    return str.replace(/\b\w/g, function(match) {
        return match.toUpperCase();
    });
}



//
function setPickupDeliveryInfo(location_data){
    //console.log("nearest_store: ", nearest_store);
    //
    if ( location_data && location_data.id ){

        //location_data.allow_pickup = 0;
        //location_data.allow_delivery = 1; location_data.miles_from_store = 20;

        //
        var str_info = "<div style='line-height: 12px;margin: 10px 0 10px'>";
        str_info += "<h4 style='color:green;'> Nearest Store - " + location_data.name + " - " + location_data.address + " </h4>";

        /*
        * Delivery
        * */
        if ( location_data.allow_delivery ){
            //
            var max_miles_from_store = parseInt(location_data.miles_from_store);
            var dist_miles = getMiles(location_data.distance_meters);
            //console.log(max_miles_from_store, dist_miles);
            //
            if ( dist_miles < max_miles_from_store  ){
                $("#delivery_container").show();
            }
            //
            else {
                str_info += "<h4 style='color:#999;'> * There is no delivery service for your area, out of range (" + dist_miles.toFixed(1) + "mi) </h4>"
            }
        }
        //
        else {
            str_info += "<h4 style='color:#999;'> * There is no delivery service for your area </h4>"
        }


        // Pickup Msgs
        if ( location_data.allow_pickup ){
            $("#pickup_container").show();
        } else {
            str_info += "<h4 style='color:#999;'> * There is no pickup service for your area</h4>";
        }

        //
        str_info += "</div>";
        $("#msgs").append(str_info);
    }
}


function readAllLocalStorage(){
    for (var a in localStorage) {
        console.log(a, ' = ', localStorage[a]);
    }
}

//
getSessionValue = function(item_name){
    //
    var item = localStorage.getItem(item_name);
    var the_item = null;
    try{
        the_item = JSON.parse(item);
    } catch(ex){
        the_item = null;
    }
    return the_item;
}


function notifySection(auth_user, auth_info){
    //
    if ( auth_info && auth_info.notifications && auth_info.notifications.length ){
        $.each(auth_info.notifications, function(idx, item){
            console.log(idx, item);
            //
            var str_icon = "";
            if (item.icon){
                str_icon += "<span class='fa fa-"+item.icon+"'></span>";
            }
            var endpoint_url = "";
            //
            if (auth_user.sale_type_id===app.PROD_TYPE_CUSTOMER_ID){
                endpoint_url = app.auth_cust_url;
            } else if (auth_user.sale_type_id===app.PROD_TYPE_STORE_ID){
                endpoint_url = app.auth_store_url;
            }
            //
            new Noty({
                theme: 'bootstrap-v4',
                type: 'info',
                timeout: 3000,
                layout: 'bottomCenter',
                text: str_icon + " " + item.message,
                callbacks: {
                    onClick: function() {
                        //
                        post({
                            url: endpoint_url + "/notifications/" + item.id + "/dsms",
                            authToken: auth_user.token,
                            Utype: auth_user.sale_type_id,
                            data: {},
                            success: function(response){
                                if (response && response.id){

                                }
                            }
                        });
                    }
                }
            }).show();

        });
    }
}

function notyMsg(type, msg){
    //
    new Noty({
        theme: 'bootstrap-v4',
        type: type,
        timeout: 3000,
        layout: 'bottomCenter',
        text: msg
    }).show();
}


function clearAutocomplete(){
    setTimeout(function(){
        $("#email").val("");
        $("#phone_number").val("");
        $("#password").val("");
    }, 750);
}



function convertirMinutosAHoras(minutos) {
    //
    minutos = Math.abs(minutos);
    //
    var horas = Math.floor(minutos / 60);
    var minutosRestantes = minutos % 60;
    //
    var resultado = '';
    //
    if (horas > 0) {
        resultado += horas + ' hrs ';
    }
    //
    if (minutosRestantes > 0) {
        resultado += minutosRestantes + ' mins';
    }
    //
    return resultado;
}


function repChar(cadena, characterToRemove, characterToReplace){
    var regex = new RegExp(characterToRemove, 'g');
    return cadena.replace(regex, characterToReplace).trim();
}

function getCantidadLetrasRestantes(textarea_id, limiteCaracteres){
    //
    var elem_descr = $(textarea_id).val();
    var cantidadCaracteres = elem_descr.length;
    var letrasRestantes = limiteCaracteres - cantidadCaracteres;
    //
    return ( letrasRestantes > 0 ) ? letrasRestantes : 0;
}


//
$(document).ready(function(){


    //
    $.whatsappButton = function(wp_phone_link, append_to){
        if (wp_phone_link){
            //
            var str_wp_btn = "<a id='whatsapp-button' href='"+wp_phone_link+"' target='_blank'>";
            str_wp_btn += "<i class='fab fa-whatsapp'></i>";
            str_wp_btn += "</a>";
            /*$("#whatsapp-button").attr("href", wp_phone).show();*/
            $(append_to).append(str_wp_btn);
        }
    }

    $.moveToTop = function(){
        $('html, body').animate({
            scrollTop: 0
        }, 500, 'easeInOutExpo');
    }

    //
    $.fn.onEnter = function(options){
        $(this).on('keypress',function(e) {
            if(e.which == 13) {
                options.onEnter();
            }
        });
    }

    //
    $.limitCharactersTextarea = function(opts){
        //
        $(opts.elemId).on("keypress", function(e) {
            //
            var cantletrasRestantes = getCantidadLetrasRestantes(opts.elemId, opts.limitTo);
            console.log(cantletrasRestantes);
            //
            if ( cantletrasRestantes === 0 ) {
                e.preventDefault();
                return false;
            }
        });
    }

});

//
function toValidUrl(string){
    return string
        .replace(/[^a-zA-Z0-9\-]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim().replace(/\s/g, '-')
        .replace(/--+/g, '-')
        .toLowerCase();
}


function getArrRange(start_val, end_val, arr_reverse){
    //
    var arr_vals = [];
    var iter_val;
    //
    if (arr_reverse){
        for (iter_val = end_val; iter_val >= start_val; iter_val--){
            //console.log(iter_val);
            arr_vals.push(iter_val);
        }
    } else {
        //
        for (iter_val = start_val; iter_val <= end_val; iter_val++){
            //console.log(iter_val);
            arr_vals.push(iter_val);
        }
    }
    //
    return arr_vals;
}


function SqrGenUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}


    


    