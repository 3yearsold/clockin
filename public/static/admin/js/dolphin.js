/*
 *  Document   : dolphin.js
 *  Author     : CaiWeiMing <314013107@qq.com>
 */

var Dolphin = function () {
    let token = localStorage.getItem('token');
    let urlToken ='';
    var queryString =window.location.href;
    var params = new URLSearchParams(queryString);
    urlToken = params.get('token');
    if (urlToken) {
        token = urlToken;
        localStorage.setItem('token', token);
    }
    /**
     * 处理ajax方式的post提交
     * @author CaiWeiMing <314013107@qq.com>
     */
    var ajaxPost = function () {
        jQuery(document).delegate('.ajax-post', 'click', function () {
            var msg, self   = jQuery(this), ajax_url = self.attr("href") || self.data("url");
            var target_form = self.attr("target-form");
            var text        = self.data('tips');
            var title       = self.data('title') || '确定要执行该操作吗？';
            var confirm_btn = self.data('confirm') || '确定';
            var cancel_btn  = self.data('cancel') || '取消';
            var form        = jQuery('form[name=' + target_form + ']');
            if (form.length === 0) {
                form = jQuery('.' + target_form);
            }
            var form_data   = form.serialize();
            if (ajax_url) {
                let searchTerm = "token";
                if (ajax_url.indexOf(searchTerm) === -1) {
                    ajax_url = token ? ajax_url+ "?token="+token :ajax_url;
                }
            }

            if ("submit" === self.attr("type") || ajax_url) {
                // 不存在“.target-form”元素则返回false
                if (undefined === form.get(0)) return false;
                // 节点标签名为FORM表单
                if ("FORM" === form.get(0).nodeName) {
                    ajax_url = ajax_url || form.get(0).getAttribute('action');;

                    // 提交确认
                    if (self.hasClass('confirm')) {
                        swal({
                            title: title,
                            text: text || '',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d26a5c',
                            confirmButtonText: confirm_btn,
                            cancelButtonText: cancel_btn,
                            closeOnConfirm: true,
                            html: false
                        }, function () {
                            pageLoader();
                            self.attr("autocomplete", "off").prop("disabled", true);
                            // 发送ajax请求
                            jQuery.post(ajax_url, form_data, {}, 'json').success(function(res) {
                                pageLoader('hide');
                                msg = res.msg;
                                if (res.url) {
                                    let searchTerm = "token";
                                    if (res.url.indexOf(searchTerm) === -1) {
                                        res.url = token ? res.url+ "?token="+token :res.url;
                                    }
                                }
                                if (res.code) {
                                    if (res.url && !self.hasClass("no-refresh")) {
                                        msg += " 页面即将自动跳转~";
                                    }
                                    tips(msg, 'success', null, null, null, res.wait);
                                    setTimeout(function () {
                                        self.attr("autocomplete", "on").prop("disabled", false);
                                        // 刷新父窗口
                                        if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                            res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                            return false;
                                        }
                                        // 关闭弹出框
                                        if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                            var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                            parent.layer.close(index);return false;
                                        }
                                        // 新窗口打开
                                        if (res.data && (res.data === '_blank' || res.data._blank)) {
                                            window.open(res.url === '' ? location.href : res.url);
                                            return false;
                                        }
                                        return self.hasClass("no-refresh") ? false : void(res.url && !self.hasClass("no-forward") ? location.href = res.url : location.reload());
                                    }, res.wait * 1000);
                                } else {
                                    jQuery(".reload-verify").length > 0 && jQuery(".reload-verify").click();
                                    tips(msg, 'danger', null, null, null, res.wait);
                                    setTimeout(function () {
                                        // 刷新父窗口
                                        if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                            res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                            return false;
                                        }
                                        // 关闭弹出框
                                        if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                            var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                            parent.layer.close(index);return false;
                                        }
                                        // 新窗口打开
                                        if (res.data && (res.data === '_blank' || res.data._blank)) {
                                            window.open(res.url === '' ? location.href : res.url);
                                            return false;
                                        }
                                        self.attr("autocomplete", "on").prop("disabled", false);
                                    }, res.wait * 1000);
                                }
                            }).fail(function (res) {
                                pageLoader('hide');
                                tips($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                                self.attr("autocomplete", "on").prop("disabled", false);
                            });
                        });
                        return false;
                    } else {
                        self.attr("autocomplete", "off").prop("disabled", true);
                    }
                } else if ("INPUT" === form.get(0).nodeName || "SELECT" === form.get(0).nodeName || "TEXTAREA" === form.get(0).nodeName) {
                    // 如果是多选，则检查是否选择
                    if (form.get(0).type === 'checkbox' && form_data === '') {
                        Dolphin.notify('请选择要操作的数据', 'warning');
                        return false;
                    }
                    // 提交确认
                    if (self.hasClass('confirm')) {
                        swal({
                            title: title,
                            text: text || '',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d26a5c',
                            confirmButtonText: confirm_btn,
                            cancelButtonText: cancel_btn,
                            closeOnConfirm: true,
                            html: false
                        }, function () {
                            pageLoader();
                            self.attr("autocomplete", "off").prop("disabled", true);

                            // 发送ajax请求
                            jQuery.post(ajax_url, form_data, {}, 'json').success(function(res) {
                                pageLoader('hide');
                                msg = res.msg;
                                if (res.url) {
                                    let searchTerm = "token";
                                    if (res.url.indexOf(searchTerm) === -1) {
                                        res.url = token ? res.url+ "?token="+token :res.url;
                                    }
                                }
                                if (res.code) {
                                    if (res.url && !self.hasClass("no-refresh")) {
                                        msg += " 页面即将自动跳转~";
                                    }
                                    tips(msg, 'success', null, null, null, res.wait);
                                    setTimeout(function () {
                                        self.attr("autocomplete", "on").prop("disabled", false);
                                        // 刷新父窗口
                                        if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                            res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                            return false;
                                        }
                                        // 关闭弹出框
                                        if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                            var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                            parent.layer.close(index);return false;
                                        }
                                        // 新窗口打开
                                        if (res.data && (res.data === '_blank' || res.data._blank)) {
                                            window.open(res.url === '' ? location.href : res.url);
                                            return false;
                                        }
                                        return self.hasClass("no-refresh") ? false : void(res.url && !self.hasClass("no-forward") ? location.href = res.url : location.reload());
                                    }, res.wait * 1000);
                                } else {
                                    jQuery(".reload-verify").length > 0 && jQuery(".reload-verify").click();
                                    tips(msg, 'danger', null, null, null, res.wait);
                                    setTimeout(function () {
                                        // 刷新父窗口
                                        if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                            res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                            return false;
                                        }
                                        // 关闭弹出框
                                        if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                            var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                            parent.layer.close(index);return false;
                                        }
                                        // 新窗口打开
                                        if (res.data && (res.data === '_blank' || res.data._blank)) {
                                            window.open(res.url === '' ? location.href : res.url);
                                            return false;
                                        }
                                        self.attr("autocomplete", "on").prop("disabled", false);
                                    }, res.wait * 1000);
                                }
                            }).fail(function (res) {
                                pageLoader('hide');
                                tips($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                                self.attr("autocomplete", "on").prop("disabled", false);
                            });
                        });
                        return false;
                    } else {
                        self.attr("autocomplete", "off").prop("disabled", true);
                    }
                } else {
                    if (self.hasClass("confirm")) {
                        swal({
                            title: title,
                            text: text || '',
                            type: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#d26a5c',
                            confirmButtonText: confirm_btn,
                            cancelButtonText: cancel_btn,
                            closeOnConfirm: true,
                            html: false
                        }, function () {
                            pageLoader();
                            self.attr("autocomplete", "off").prop("disabled", true);
                            form_data = form.find("input,select,textarea").serialize();

                            // 发送ajax请求
                            jQuery.post(ajax_url, form_data, {}, 'json').success(function(res) {
                                pageLoader('hide');
                                msg = res.msg;
                                if (res.url) {
                                    let searchTerm = "token";
                                    if (res.url.indexOf(searchTerm) === -1) {
                                        res.url = token ? res.url+ "?token="+token :res.url;
                                    }
                                }
                                if (res.code) {
                                    if (res.url && !self.hasClass("no-refresh")) {
                                        msg += " 页面即将自动跳转~";
                                    }
                                    tips(msg, 'success', null, null, null, res.wait);
                                    setTimeout(function () {
                                        self.attr("autocomplete", "on").prop("disabled", false);
                                        // 刷新父窗口
                                        if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                            res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                            return false;
                                        }
                                        // 关闭弹出框
                                        if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                            var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                            parent.layer.close(index);return false;
                                        }
                                        // 新窗口打开
                                        if (res.data && (res.data === '_blank' || res.data._blank)) {
                                            window.open(res.url === '' ? location.href : res.url);
                                            return false;
                                        }
                                        return self.hasClass("no-refresh") ? false : void(res.url && !self.hasClass("no-forward") ? location.href = res.url : location.reload());
                                    }, res.wait * 1000);
                                } else {
                                    jQuery(".reload-verify").length > 0 && jQuery(".reload-verify").click();
                                    tips(msg, 'danger', null, null, null, res.wait);
                                    setTimeout(function () {
                                        // 刷新父窗口
                                        if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                            res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                            return false;
                                        }
                                        // 关闭弹出框
                                        if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                            var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                            parent.layer.close(index);return false;
                                        }
                                        // 新窗口打开
                                        if (res.data && (res.data === '_blank' || res.data._blank)) {
                                            window.open(res.url === '' ? location.href : res.url);
                                            return false;
                                        }
                                        self.attr("autocomplete", "on").prop("disabled", false);
                                    }, res.wait * 1000);
                                }
                            }).fail(function (res) {
                                pageLoader('hide');
                                tips($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                                self.attr("autocomplete", "on").prop("disabled", false);
                            });
                        });
                        return false;
                    } else {
                        form_data = form.find("input,select,textarea").serialize();
                        self.attr("autocomplete", "off").prop("disabled", true);
                    }
                }

                // 直接发送ajax请求
                jQuery.post(ajax_url, form_data, {}, 'json').success(function(res) {
                    pageLoader('hide');
                    msg = res.msg;
                    if (res.url) {
                        let searchTerm = "token";
                        if (res.url.indexOf(searchTerm) === -1) {
                            res.url = token ? res.url+ "?token="+token :res.url;
                        }
                    }
                    if (res.code) {
                        if (res.url && !self.hasClass("no-refresh")) {
                            msg += "， 页面即将自动跳转~";
                        }
                        tips(msg, 'success', null, null, null, res.wait);
                        setTimeout(function () {
                            self.attr("autocomplete", "on").prop("disabled", false);
                            // 刷新父窗口
                            if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                return false;
                            }
                            // 关闭弹出框
                            if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                parent.layer.close(index);return false;
                            }
                            // 新窗口打开
                            if (res.data && (res.data === '_blank' || res.data._blank)) {
                                window.open(res.url === '' ? location.href : res.url);
                                return false;
                            }
                            return self.hasClass("no-refresh") ? false : void(res.url && !self.hasClass("no-forward") ? location.href = res.url : location.reload());
                        }, res.wait * 1000);
                    } else {
                        jQuery(".reload-verify").length > 0 && jQuery(".reload-verify").click();
                        tips(msg, 'danger', null, null, null, res.wait);
                        setTimeout(function () {
                            // 刷新父窗口
                            if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                return false;
                            }
                            // 关闭弹出框
                            if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                parent.layer.close(index);return false;
                            }
                            // 新窗口打开
                            if (res.data && (res.data === '_blank' || res.data._blank)) {
                                window.open(res.url === '' ? location.href : res.url);
                                return false;
                            }
                            self.attr("autocomplete", "on").prop("disabled", false);
                        }, res.wait * 1000);
                    }
                }).fail(function (res) {
                    pageLoader('hide');
                    tips($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                    self.attr("autocomplete", "on").prop("disabled", false);
                });
            }

            return false;
        });
    };

    /**
     * 处理ajax方式的get提交
     * @author CaiWeiMing <314013107@qq.com>
     */
    var ajaxGet = function () {
        jQuery(document).delegate('.ajax-get', 'click', function () {
            var msg, self = $(this), text = self.data('tips'), ajax_url = self.attr("href") || self.data("url");
            var title       = self.data('title') || '确定要执行该操作吗？';
            var confirm_btn = self.data('confirm') || '确定';
            var cancel_btn  = self.data('cancel') || '取消';
            if (ajax_url) {
                let searchTerm = "token";
                if (ajax_url.indexOf(searchTerm) === -1) {
                    ajax_url = token ? ajax_url+ "?token="+token :ajax_url;
                }
            }
            // 执行确认
            if (self.hasClass('confirm')) {
                swal({
                    title: title,
                    text: text || '',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d26a5c',
                    confirmButtonText: confirm_btn,
                    cancelButtonText: cancel_btn,
                    closeOnConfirm: true,
                    html: false
                }, function () {
                    pageLoader();
                    self.attr("autocomplete", "off").prop("disabled", true);

                    // 发送ajax请求
                    jQuery.get(ajax_url, {}, {}, 'json').success(function(res) {
                        pageLoader('hide');
                        msg = res.msg;
                        if (res.url) {
                            let searchTerm = "token";
                            if (res.url.indexOf(searchTerm) === -1) {
                                res.url = token ? res.url+ "?token="+token :res.url;
                            }
                        }
                        if (res.code) {
                            if (res.url && !self.hasClass("no-refresh")) {
                                msg += " 页面即将自动跳转~";
                            }
                            tips(msg, 'success', null, null, null, res.wait);
                            setTimeout(function () {
                                self.attr("autocomplete", "on").prop("disabled", false);
                                // 刷新父窗口
                                if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                    res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                    return false;
                                }
                                // 关闭弹出框
                                if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                    var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                    parent.layer.close(index);return false;
                                }
                                // 新窗口打开
                                if (res.data && (res.data === '_blank' || res.data._blank)) {
                                    window.open(res.url === '' ? location.href : res.url);
                                    return false;
                                }
                                return self.hasClass("no-refresh") ? false : void(res.url && !self.hasClass("no-forward") ? location.href = res.url : location.reload());
                            }, res.wait * 1000);
                        } else {
                            tips(msg, 'danger', null, null, null, res.wait);
                            setTimeout(function () {
                                // 刷新父窗口
                                if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                    res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                    return false;
                                }
                                // 关闭弹出框
                                if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                    var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                    parent.layer.close(index);return false;
                                }
                                // 新窗口打开
                                if (res.data && (res.data === '_blank' || res.data._blank)) {
                                    window.open(res.url === '' ? location.href : res.url);
                                    return false;
                                }
                                self.attr("autocomplete", "on").prop("disabled", false);
                            }, res.wait * 1000);
                        }
                    }).fail(function (res) {
                        pageLoader('hide');
                        tips($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                        self.attr("autocomplete", "on").prop("disabled", false);
                    });
                });
            } else {
                pageLoader();
                self.attr("autocomplete", "off").prop("disabled", true);

                // 发送ajax请求
                jQuery.get(ajax_url, {}, {}, 'json').success(function(res) {
                    pageLoader('hide');
                    msg = res.msg;
                    if (res.url) {
                        let searchTerm = "token";
                        if (res.url.indexOf(searchTerm) === -1) {
                            res.url = token ? res.url+ "?token="+token :res.url;
                        }
                    }
                    if (res.code) {
                        if (res.url && !self.hasClass("no-refresh")) {
                            msg += " 页面即将自动跳转~";
                        }
                        tips(msg, 'success', null, null, null, res.wait);
                        setTimeout(function () {
                            self.attr("autocomplete", "on").prop("disabled", false);
                            // 刷新父窗口
                            if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                return false;
                            }
                            // 关闭弹出框
                            if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                parent.layer.close(index);return false;
                            }
                            // 新窗口打开
                            if (res.data && (res.data === '_blank' || res.data._blank)) {
                                window.open(res.url === '' ? location.href : res.url);
                                return false;
                            }
                            return self.hasClass("no-refresh") ? false : void(res.url && !self.hasClass("no-forward") ? location.href = res.url : location.reload());
                        }, res.wait * 1000);
                    } else {
                        tips(msg, 'danger', null, null, null, res.wait);
                        setTimeout(function () {
                            // 刷新父窗口
                            if (res.data && (res.data === '_parent_reload' || res.data._parent_reload)) {
                                res.url === '' || res.url === location.href ? parent.location.reload() : parent.location.href = res.url;
                                return false;
                            }
                            // 关闭弹出框
                            if (res.data && (res.data === '_close_pop' || res.data._close_pop)) {
                                var index = parent.layer.getFrameIndex(window.name); //获取窗口索引
                                parent.layer.close(index);return false;
                            }
                            // 新窗口打开
                            if (res.data && (res.data === '_blank' || res.data._blank)) {
                                window.open(res.url === '' ? location.href : res.url);
                                return false;
                            }
                            self.attr("autocomplete", "on").prop("disabled", false);
                        }, res.wait * 1000);
                    }
                }).fail(function (res) {
                    pageLoader('hide');
                    tips($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                    self.attr("autocomplete", "on").prop("disabled", false);
                });
            }

            return false;
        });
    };

    /**
     * 处理普通方式的get提交
     * @author CaiWeiMing <314013107@qq.com>
     */
    var jsGet = function () {
        jQuery(document).delegate('.js-get', 'click', function () {
            var self = $(this), text = self.data('tips'), url = self.attr("href") || self.data("url");
            var target_form = self.attr("target-form");
            var form        = jQuery('form[name=' + target_form + ']');
            var form_data   = form.serialize() || [];
            var title       = self.data('title') || '确定要执行该操作吗？';
            var confirm_btn = self.data('confirm') || '确定';
            var cancel_btn  = self.data('cancel') || '取消';

            if (form.length === 0) {
                form = jQuery('.' + target_form + '[type=checkbox]:checked');
                form.each(function () {
                    form_data.push($(this).val());
                });
                form_data = form_data.join(',');
            }

            if (form_data === '') {
                Dolphin.notify('请选择要操作的数据', 'warning');
                return false;
            }

            if (url.indexOf('?') !== -1) {
                url += '&' + target_form + '=' + form_data;
            } else {
                url += '?' + target_form + '=' + form_data;
            }
            // 执行确认
            if (self.hasClass('confirm')) {
                swal({
                    title: title,
                    text: text || '',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d26a5c',
                    confirmButtonText: confirm_btn,
                    cancelButtonText: cancel_btn,
                    closeOnConfirm: true,
                    html: false
                }, function () {
                    if (self.hasClass('js-blank')) {
                        window.open(url);
                    }  else {
                        location.href = url;
                    }
                });
            } else {
                if (self.hasClass('js-blank')) {
                    window.open(url);
                }  else {
                    location.href = url;
                }
            }

            return false;
        });
    };

    /**
     * 顶部菜单
     * @author CaiWeiMing <314013107@qq.com>
     */
    var topMenu = function () {
        $('.top-menu').click(function () {
            var $target = $(this).attr('target');
            var data = {
                module_id: $(this).data('module-id') || '',
                module: $(this).data('module') || '',
                controller: $(this).data('controller') || ''
            };

            let token = localStorage.getItem('token');
            let urlToken ='';
            var queryString =window.location.search;
            var params = new URLSearchParams(queryString);
            urlToken = params.get('token');
            if (urlToken) {
                token = urlToken;
                localStorage.setItem('token', token);
            }
            if ($('#nav-' + data.module_id).length) {

                if (token) {
                    var url = $('#nav-' + data.module_id).find('a').not('.nav-submenu').first().attr('href')+"?token="+token;
                    location.href = url;
                } else {
                    location.href = $('#nav-' + data.module_id).find('a').not('.nav-submenu').first().attr('href');
                }
            } else {
                if (token) {
                    var post_url =dolphin.top_menu_url + "?token="+token;
                } else {
                    var post_url = dolphin.top_menu_url;
                }

                $.post(post_url, data, function (res) {
                    if (res.code) {
                        if (res.data === '') {
                            tips('暂无无节点权限', 'danger');return false;
                        }

                        let searchTerm = "token";
                        if (res.data.indexOf(searchTerm) === -1) {
                            var openUrl  = token ? res.data+ "?token="+token :res.data;
                        } else {
                            var openUrl =res.data;
                        }
                        if ($target === '_self') {
                            location.href = openUrl;
                        } else {
                            window.open(openUrl);
                        }
                    } else {
                        tips(res.msg, 'danger');
                        setTimeout(function () {
                            location.reload();
                        }, 1500);
                    }
                }).fail(function (res) {
                    tips($(res.responseText).find('h1').text() || '服务器内部错误~', 'danger');
                });
            }
            return false;
        });
    };

    /**
     * 页面小提示
     * @param $msg 提示信息
     * @param $type 提示类型:'info', 'success', 'warning', 'danger'
     * @param $icon 图标，例如：'fa fa-user' 或 'glyphicon glyphicon-warning-sign'
     * @param $from 'top' 或 'bottom'
     * @param $align 'left', 'right', 'center'
     * @param $delay
     * @author CaiWeiMing <314013107@qq.com>
     */
    var tips = function ($msg, $type, $icon, $from, $align, $delay) {
        $type  = $type || 'info';
        $from  = $from || 'top';
        $align = $align || 'center';
        $delay = $delay || 3;
        $enter = $type === 'success' ? 'animated fadeInUp' : 'animated shake';

        jQuery.notify({
            icon: $icon,
            message: $msg
        },
        {
            element: 'body',
            type: $type,
            allow_dismiss: true,
            newest_on_top: true,
            showProgressbar: false,
            placement: {
                from: $from,
                align: $align
            },
            offset: 20,
            spacing: 10,
            z_index: 10800,
            delay: $delay * 1000,
            timer: 1000,
            animate: {
                enter: $enter,
                exit: 'animated fadeOutDown'
            }
        });
    };

    /**
     * 页面加载提示
     * @param $mode 'show', 'hide'
     * @author CaiWeiMing <314013107@qq.com>
     */
    var pageLoader = function ($mode) {
        var $loadingEl = jQuery('#loading');
        $mode          = $mode || 'show';

        if ($mode === 'show') {
            if ($loadingEl.length) {
                $loadingEl.fadeIn(250);
            } else {
                jQuery('body').prepend('<div id="loading"><div class="loading-box"><i class="fa fa-2x fa-cog fa-spin"></i> <span class="loding-text">请稍等...</span></div></div>');
            }
        } else if ($mode === 'hide') {
            if ($loadingEl.length) {
                $loadingEl.fadeOut(250);
            }
        }

        return false;
    };

    /**
     * 启用图标搜索
     * @author CaiWeiMing <314013107@qq.com>
     */
    var iconSearchLoader = function () {
        // Set variables
        var $searchItems = jQuery('.js-icon-list > li');
        var $searchValue = '';

        // When user types
        jQuery('.js-icon-search').on('keyup', function(){
            $searchValue = jQuery(this).val().toLowerCase();

            if ($searchValue.length) { // If more than 2 characters, search the icons
                $searchItems.hide();

                jQuery('code', $searchItems)
                    .each(function(){
                        if (jQuery(this).text().match($searchValue)) {
                            jQuery(this).parent('li').show();
                        }
                    });
            } else if ($searchValue.length === 0) { // If text deleted show all icons
                $searchItems.show();
            }
        });
    };

    /**
     * 刷新页面
     * @author CaiWeiMing <314013107@qq.com>
     */
    var pageReloadLoader = function () {
        // 刷新页面
        $('.page-reload').click(function () {
            location.reload();
        });
    };

    /**
     * 初始化图片查看
     * @author CaiWeiMing <314013107@qq.com>
     */
    var viewerLoader = function () {
        $('.gallery-list,.uploader-list').each(function () {
            $(this).viewer('destroy');
            $(this).viewer({url: 'data-original'});
        });
    };



    var setToken = function (){
        let token = localStorage.getItem('token');
        let urlToken ='';
        // 获取当前 URL 的查询参数部分（?之后的内容）
        var queryString = window.location.search;
        var params = new URLSearchParams(queryString);
        urlToken = params.get('token');   // :ml-citation{ref="1,4" data="citationList"}
        if (urlToken) {
            token = urlToken;
            localStorage.setItem('token', token);
        }
    };
    return {
        // 初始化
        init: function () {
            ajaxPost();
            ajaxGet();
            jsGet();
            topMenu();
            pageReloadLoader();
            setToken();
        },
        // 页面加载提示
        loading: function ($mode) {
            pageLoader($mode);
        },
        // 页面小提示
        notify: function ($msg, $type, $icon, $from, $align, $delay) {
            tips($msg, $type, $icon, $from, $align, $delay);
        },
        // 启用图标搜索
        iconSearch: function () {
            iconSearchLoader();
        },
        // 初始化图片查看
        viewer: function () {
            viewerLoader();
        }
    };
}();

// Initialize app when page loads
jQuery(function () {
    Dolphin.init();
});