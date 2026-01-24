const puockGlobalData = {
    loads: {}
}

const TYPE_PRIMARY = "primary"
const TYPE_WARNING = "warning"
const TYPE_DANGER = "danger"
const TYPE_SUCCESS = "success"
const TYPE_INFO = "info"

class Puock {

    data = {
        tag: 'puock',
        pc:true,
        params: {
            home: null,
            use_post_menu: false,
            is_single: false,
            is_pjax: false,
            vd_comment: false,
            vd_gt_id: null,
            vd_type: null,
            main_lazy_img: false,
            link_blank_open: false,
            async_view_id: null,
            mode_switch: false,
            async_view_generate_time: null,
            off_img_viewer:false,
            off_code_highlighting:false
        },
        comment: {
            loading: false,
            time: 5,
            val: null,
            replyId: null,
            submitting: false
        },
        instance: {},
        modalStorage: {}
    }

    // 全局一次加载或注册的事件
    onceInit() {
        this.pageInit()
        $(document).on("click", ".fancybox", (e) => {
            if (e && e.preventDefault) e.preventDefault();
        });
        $(document).on("click", ".entry-content a", (e) => {
            if (this.data.params.off_img_viewer) return;
            const a = e.currentTarget;
            if (!a || !a.querySelector) return;
            if (!a.querySelector("img")) return;
            const href = (a.getAttribute("href") || "").trim();
            if (!href) return;
            const isImageHref = /(\.(?:avif|bmp|gif|jpe?g|png|svg|webp))(?:[?#].*)?$/i.test(href) || /^(?:data|blob):/i.test(href);
            if (!isImageHref) return;
            if (e && e.preventDefault) e.preventDefault();
        });
        $(document).on("click", "#rb-float-actions>div", (e) => {
            const el = $(this.ct(e));
            const to = el.data("to");
            if (to) {
                const scroll_val = to === 'top' ? 0 : window.document.body.clientHeight;
                $('html,body').stop().animate({scrollTop: scroll_val}, 50)
                return;
            }
            const toArea = el.data("to-area");
            if (toArea) {
                this.gotoArea(toArea)
            }
        });
        $(document).on("click", ".colorMode", () => {
            this.modeChange(null, true);
        });

        if (this.data.params.is_pjax) {
            this.instanceClickLoad()
        }
        this.initBasicDOMEvent()
        this.sidebarMenuEventInit()
        this.searchInit()
        this.eventShareStart()
        this.modeInit();
        this.registerMobileMenu()
        this.registerModeChangeEvent()
        this.eventCommentPageChangeEvent()
        this.eventCommentPreSubmit()
        this.eventSendPostLike()
        this.eventPostMainBoxResize()
        this.swiperOnceEvent()
        this.initModalToggle()
        this.loginInit()
        this.smileyModalInit()
        this.posterInit()
        this.rewardInit()
        this.shareModalInit()
        this.detectDevice()
        window.addEventListener('resize', ()=>this.detectDevice());
        layer.config({shade: 0.5})
        // 新增：首次加载时初始化评论相关事件
        this.initCommentEvents();
    }

    pageInit() {
        this.loadParams()
        this.pageChangeInit()
        if (this.data.params.is_single) {
            if (this.data.params.use_post_menu) {
                this.generatePostMenuHTML()
            }
        }
    }

    instanceClickLoad() {
        InstantClick.init('mousedown');
        InstantClick.go = (url) => {
            const link = document.createElement('a');
            link.href = url;
            document.body.appendChild(link);
            link.click();
        }
        InstantClick.on('change', (e) => {
            this.loadParams();
            this.pageChangeInit()
        })
        
        // 修复PJAX评论问题：确保评论表单重新初始化
        InstantClick.on('change', (e) => {
            // 重置提交状态 - 修复第二次评论卡住的问题
            this.data.comment.submitting = false;
            setTimeout(() => {
                this.initCommentEvents();
                this.loadCommentInfo();
                // 重新绑定评论表单提交事件
                this.eventCommentPreSubmit();
                // 重新初始化评论分页事件
                this.eventCommentPageChangeEvent();
            }, 100);
        })
        // InstantClick.on('receive',(url, body, title)=>{
        //     console.log(body)
        //     this.loadParams($(body))
        // })
        this.loadCommentInfo();
    }

    ct(e) {
        return e.currentTarget
    }

    detectDevice() {
        const screenWidth = window.innerWidth;
        this.data.pc = screenWidth >= 768
    }

    initBasicDOMEvent() {
        // el show or hide event
        $(document).on("click", ".toggle-el-show-hide", (e) => {
            const el = $(this.ct(e));
            const target = $(el.attr("data-target"));
            const self = $(el.attr("data-self"));
            const modalTitle = el.attr("data-modal-title");
            if (target.hasClass("d-none")) {
                self.addClass("d-none");
                target.removeClass("d-none");
            } else {
                self.removeClass("d-none");
                target.addClass("d-none");
            }
            if (modalTitle) {
                el.closest(".modal").find(".modal-title").text(modalTitle);
            }
        });
        // form ajax submit
        $(document).on("submit", ".ajax-form", (e) => {
            // 如果是登录弹窗表单，允许原生提交
            if ($(e.target).attr('id') === 'front-login-form') {
                return true;
            }
            e.preventDefault();
            const form = $(this.ct(e));
            const formEls = form.find(":input")
            if (formEls.length === 0) {
                this.toast('表单元素为空', TYPE_DANGER)
                return false;
            }
            for (let i = 0; i < formEls.length; i++) {
                const el = $(formEls[i]);
                if (el.attr("data-required") !== undefined && el.val() === "") {
                    this.toast(el.attr("data-tip") || el.attr("placeholder"), TYPE_WARNING)
                    return false;
                }
            }
            const validateType = form.data("validate");
            const startSubmit = (args = {}) => {
                const url = form.attr("action");
                const method = form.attr("method");
                const data = this.parseFormData(form, args);
                const dataType = "json";
                const successTip = form.attr("data-success");
                const errorTip = form.attr("data-error");
                const loading = this.startLoading()
                $.ajax({
                    url, method, data, dataType,
                    success: (res) => {
                        this.stopLoading(loading)
                        if (res.code === 0 || res.success) {
                            this.toast(res.msg || successTip, TYPE_SUCCESS)
                            if (form.data("no-reset") === undefined) {
                                form.trigger("reset")
                            }
                            if (res.data) {
                                const resData = res.data
                                if (resData.action) {
                                    setTimeout(() => {
                                        switch (resData.action) {
                                            case 'reload':
                                                this.goUrl(window.location.href)
                                                break
                                        }
                                    }, 500)
                                }
                            }
                        } else {
                            this.toast(res.msg || res.data || errorTip, TYPE_DANGER)
                        //    this.loadCommentCaptchaImage(form, true)
                        }
                    },
                    error: (e) => {
                        this.stopLoading(loading)
                        this.toast(`请求错误：${e.statusText}`, TYPE_DANGER)
                    //    this.loadCommentCaptchaImage(form, true)
                    }
                })
            }
            if (validateType === 'gt') {
                this.gt.validate((code) => {
                    startSubmit(code)
                });
            } else {
                startSubmit()
            }
            return false;
        })
    }

    loginInit() {
        $(document).off("click", ".pk-login-open");
        $(document).on("click", ".pk-login-open", (e) => {
            if (e && e.preventDefault && e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey) {
                e.preventDefault();
            }
            if (e && e.stopImmediatePropagation) e.stopImmediatePropagation();
            if (e && e.stopPropagation) e.stopPropagation();
            const tpl = document.getElementById("pk-login-template");
            if (!tpl) {
                this.toast("登录组件未加载", TYPE_WARNING);
                return;
            }
            const html = (tpl.innerHTML || "").trim();
            if (!html) {
                this.toast("登录组件未加载", TYPE_WARNING);
                return;
            }
            const url = (window.location.href || "").split("#")[0];
            const dataId = (typeof window.SparkMD5 !== "undefined" && window.SparkMD5.hash) ? window.SparkMD5.hash("login|" + url) : String(Date.now());
            this.modalLoadRender(dataId, html, "登入", false, false);

            const modalRoot = document.getElementById("pk-modal-" + dataId);
            if (!modalRoot) return;
            const refererEl = modalRoot.querySelector("input[name='referer']");
            if (refererEl) refererEl.value = url;
            const nameEl = modalRoot.querySelector("#_front_login_username");
            if (nameEl && nameEl.focus) nameEl.focus();
        });

        $(document).off("keydown", ".pk-login-open");
        $(document).on("keydown", ".pk-login-open", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                $(e.currentTarget).trigger("click");
            }
        });
    }

    smileyModalInit() {
        $(document).off("click", ".pk-smiley-open");
        $(document).on("click", ".pk-smiley-open", (e) => {
            if (e && e.preventDefault) e.preventDefault();
            const tpl = document.getElementById("pk-smiley-template");
            if (!tpl) {
                this.toast("表情组件未加载", TYPE_WARNING);
                return;
            }
            const html = (tpl.innerHTML || "").trim();
            if (!html) {
                this.toast("表情组件未加载", TYPE_WARNING);
                return;
            }
            const url = (window.location.href || "").split("#")[0];
            const dataId = (typeof window.SparkMD5 !== "undefined" && window.SparkMD5.hash) ? window.SparkMD5.hash("smiley|" + url) : String(Date.now());
            this.modalLoadRender(dataId, html, "表情", false, false);
        });
    }

    pageLinkBlankOpenInit() {
        if (this.data.params.link_blank_open) {
            $(".entry-content").find("a").each((_, item) => {
                if (item && item.querySelector && item.querySelector("img")) {
                    const href = (item.getAttribute('href') || '').trim();
                    const isImageHref = /(\.(?:avif|bmp|gif|jpe?g|png|svg|webp))(?:[?#].*)?$/i.test(href) || /^(?:data|blob):/i.test(href);
                    if (isImageHref) return;
                }
                $(item).attr('target', 'blank')
            })
        }
    }

    searchInit() {
        const toggle = () => {
            const search = $("#search");
            const open = search.attr("data-open") === "true";
            let tag = open ? 'Out' : 'In';
            search.attr("class", "animated fade" + tag + "Left");
            $("#search-backdrop").attr("class", "modal-backdrop animated fade" + tag + "Right");
            search.attr("data-open", !open);
            if (!open) {
                search.find("input").focus();
            }
        }
        $(document).on("click", ".search-modal-btn", () => {
            toggle();
        });
        $(document).on("click", "#search-backdrop", () => {
            toggle();
        })
        $(document).on("submit", ".global-search-form", (e) => {
            e.preventDefault();
            const el = $(this.ct(e));
            this.goUrl(el.attr("action") + "?" + el.serialize())
        })
    }

    goUrl(url) {
        if (this.data.params.is_pjax) {
            InstantClick.go(url)
        } else {
            window.location.href = url
        }
    }

    gt = {
        validate: (success = undefined) => {
            this.data.instance.gt_callback = success
        //    this.data.instance.gt.showCaptcha();
        }
    }

    rippleInit() {
        const args = {
            debug: false,
            on: 'mousedown',
            opacity: 0.4,
            color: "var(--pk-c-light)",
            multi: false,
            duration: 0.6,
            rate: function (pxPerSecond) {
                return pxPerSecond;
            },
            easing: 'linear'
        }
        jQuery.ripple(".btn", args);
        jQuery.ripple(".ww", args);
    }

    eventShareStart() {
        $(document).on("click", ".share-to", (e) => {
            const id = $(this.ct(e)).attr("data-id");
            if (id === 'wx') return;
            const url = window.location.href;
            const title = $("#post-title").text();
            const wb_key = '';
            let to = null;
            switch (id) {
                case 'wb':
                    to = 'https://service.weibo.com/share/share.php?pic=&title=' + title + '&url=' + url + '&appkey=' + wb_key;
                    break;
                case 'qzone':
                    to = 'https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?title=' + title + '&url=' + url;
                    break;
                case 'tw':
                    to = 'https://twitter.com/intent/tweet?url=' + url;
                    break;
                case 'fb':
                    to = 'https://www.facebook.com/sharer.php?u' + url;
                    break;
            }
            if (to) window.open(to, '_blank');
        });
    }

    posterInit() {
        $(document).off("click", ".post-poster-open");
        $(document).on("click", ".post-poster-open", async (e) => {
            const trigger = $(this.ct(e));
            const title = (trigger.data("poster-title") || $("#post-title").text() || document.title || "").toString().trim();
            const excerpt = (trigger.data("poster-excerpt") || "").toString().trim();
            const coverRaw = (trigger.data("poster-cover") || "").toString().trim();
            const coverDefault = (trigger.data("poster-cover-default") || "").toString().trim();
            const logo = (trigger.data("poster-logo") || "").toString().trim();
            const url = (window.location.href || "").split("#")[0];

            const dataId = (typeof window.SparkMD5 !== "undefined" && window.SparkMD5.hash) ? window.SparkMD5.hash("poster|" + url + "|" + title) : String(Date.now());
            const posterUid = "pk-poster-" + dataId;
            const qrUid = "pk-poster-qr-" + dataId;
            const coverImgUid = "pk-poster-cover-" + dataId;
            const coverBoxUid = "pk-poster-coverbox-" + dataId;
            const logoImgUid = "pk-poster-logo-" + dataId;

            const toAbsHref = (val) => {
                try {
                    if (!val) return "";
                    return new URL(String(val), window.location.href).href;
                } catch (err) {
                    return "";
                }
            };

            const normalizeSameHostScheme = (href) => {
                try {
                    if (!href) return "";
                    const u = new URL(href);
                    if (u.host === window.location.host && u.protocol !== window.location.protocol) {
                        u.protocol = window.location.protocol;
                        return u.href;
                    }
                    return href;
                } catch (err) {
                    return href || "";
                }
            };

            const isSameOrigin = (href) => {
                try {
                    if (!href) return false;
                    return new URL(href).origin === window.location.origin;
                } catch (err) {
                    return false;
                }
            };

            const coverRawHref = normalizeSameHostScheme(toAbsHref(coverRaw));
            const coverDefaultHref = normalizeSameHostScheme(toAbsHref(coverDefault));
            let cover = "";
            if (coverRawHref) {
                if (isSameOrigin(coverRawHref)) {
                    cover = coverRawHref;
                } else {
                    cover = coverDefaultHref;
                    if (coverDefaultHref) this.toast("海报封面图片跨域，已使用默认封面", TYPE_WARNING);
                }
            }
            if (!cover) cover = coverDefaultHref;

            const logoHref = normalizeSameHostScheme(toAbsHref(logo));
            let logoSrc = "";
            if (logoHref) {
                if (isSameOrigin(logoHref)) {
                    logoSrc = logoHref;
                }
            }
            const logoHtml = logoSrc ? `<img class="logo" id="${logoImgUid}" crossorigin="anonymous" referrerpolicy="no-referrer" src="${logoSrc}" alt="logo">` : "";

            const html = `
<div class="post-poster">
  <div class="post-poster-main" id="${posterUid}">
    <div class="cover" id="${coverBoxUid}">
      <img id="${coverImgUid}" crossorigin="anonymous" referrerpolicy="no-referrer" src="${cover || ""}" alt="poster">
    </div>
    <div class="content">
      <p class="title mt20 fs16"></p>
      <p class="excerpt text-3line fs14 mt20 c-sub"></p>
      <div class="info mt20">
        <div class="qrcode" id="${qrUid}"></div>
        ${logoHtml}
      </div>
      <p class="tip c-sub fs12 mt20 p-flex-center"><i class="fas fa-qrcode"></i>&nbsp;长按识别二维码查看文章内容</p>
    </div>
  </div>
</div>
            `.trim();

            this.modalLoadRender(dataId, html, "海报", false, false);
            const modalRoot = $("#pk-modal-" + dataId);
            modalRoot.find("#" + posterUid + " .title").text(title);
            if (excerpt) {
                modalRoot.find("#" + posterUid + " .excerpt").text(excerpt);
            } else {
                modalRoot.find("#" + posterUid + " .excerpt").addClass("d-none");
            }

            try {
                await this.ensureQRCodeReady(5000);
                const qrEl = document.getElementById(qrUid);
                if (qrEl) {
                    qrEl.innerHTML = "";
                    // eslint-disable-next-line no-undef
                    new QRCode(qrEl, {
                        text: url,
                        width: 120,
                        height: 120,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: (typeof QRCode !== "undefined" && QRCode.CorrectLevel) ? QRCode.CorrectLevel.M : undefined,
                    });
                }
            } catch (err) {
                console.error(err);
                this.toast("二维码组件未加载", TYPE_WARNING);
            }

            const waitImgStatus = (imgEl, timeoutMs = 3500) => {
                if (!imgEl) return Promise.resolve("error");
                if (!imgEl.getAttribute("src")) return Promise.resolve("error");
                if (imgEl.complete) return Promise.resolve(imgEl.naturalWidth > 0 ? "loaded" : "error");
                return new Promise((resolve) => {
                    let done = false;
                    const finish = (status) => {
                        if (done) return;
                        done = true;
                        imgEl.onload = null;
                        imgEl.onerror = null;
                        clearTimeout(t);
                        resolve(status);
                    };
                    const t = setTimeout(() => finish("timeout"), timeoutMs);
                    imgEl.onload = () => finish("loaded");
                    imgEl.onerror = () => finish("error");
                });
            };

            const ensureCoverOk = async () => {
                const coverImg = document.getElementById(coverImgUid);
                const coverBox = document.getElementById(coverBoxUid);
                if (!coverImg || !coverBox) return;
                const st = await waitImgStatus(coverImg, 3500);
                if (st === "loaded" || st === "timeout") return;
                if (coverDefaultHref && coverImg.src !== coverDefaultHref) {
                    coverImg.src = coverDefaultHref;
                    const st2 = await waitImgStatus(coverImg, 3500);
                    if (st2 === "loaded") return;
                }
                coverBox.style.display = "none";
            };

            const ensureLogoOk = async () => {
                const logoImg = document.getElementById(logoImgUid);
                if (!logoImg) return;
                const st = await waitImgStatus(logoImg, 3500);
                if (st === "error") logoImg.remove();
            };

            const loading = this.startLoading();
            try {
                const node = document.getElementById(posterUid);
                if (!node || typeof window.html2canvas === "undefined") {
                    this.toast("html2canvas 未加载", TYPE_DANGER);
                    return;
                }
                await ensureCoverOk();
                await ensureLogoOk();
                await this.waitImagesLoaded(node, 8000);
                const canvas = await window.html2canvas(node, {
                    allowTaint: true,
                    useCORS: true,
                    backgroundColor: "#ffffff",
                });
                const dataUrl = canvas.toDataURL("image/png");
                $("#" + posterUid).html(`<img class="result" src="${dataUrl}" alt="poster">`);
            } catch (err) {
                console.error(err);
                this.toast("生成海报失败，请到Console查看错误信息", TYPE_DANGER);
            } finally {
                this.stopLoading(loading);
            }
        });

        $(document).off("keydown", ".post-poster-open");
        $(document).on("keydown", ".post-poster-open", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                $(e.currentTarget).trigger("click");
            }
        });
    }

    rewardInit() {
        $(document).off("click", ".reward-modal-open");
        $(document).on("click", ".reward-modal-open", () => {
            const tpl = document.getElementById("pk-reward-template");
            if (!tpl) {
                this.toast("请在主题设置中填写赞赏二维码地址", TYPE_WARNING);
                return;
            }
            const html = tpl.innerHTML || "";
            if (!html.trim()) {
                this.toast("请在主题设置中填写赞赏二维码地址", TYPE_WARNING);
                return;
            }
            const url = (window.location.href || "").split("#")[0];
            const dataId = (typeof window.SparkMD5 !== "undefined" && window.SparkMD5.hash) ? window.SparkMD5.hash("reward|" + url) : String(Date.now());
            this.modalLoadRender(dataId, html, "赞赏", false, false);
        });

        $(document).off("keydown", ".reward-modal-open");
        $(document).on("keydown", ".reward-modal-open", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                $(e.currentTarget).trigger("click");
            }
        });
    }

    shareModalInit() {
        $(document).off("click", ".share-modal-open");
        $(document).on("click", ".share-modal-open", async () => {
            const url = (window.location.href || "").split("#")[0];
            const title = ($("#post-title").text() || document.title || "").toString().trim();
            const dataId = (typeof window.SparkMD5 !== "undefined" && window.SparkMD5.hash) ? window.SparkMD5.hash("share|" + url) : String(Date.now());
            const qrUid = "pk-share-qr-" + dataId;
            const qrBoxUid = "pk-share-qrbox-" + dataId;
            const copyUid = "pk-share-copy-" + dataId;
            const wxUid = "pk-share-wx-" + dataId;

            const html = `
<div class="d-flex justify-content-center w-100">
  <div data-id="wb" class="share-to circle-button circle-sm circle-hb text-center bg-danger text-light" title="微博">
    <i class="fa-brands fa-weibo t-md"></i>
  </div>
  <div id="${wxUid}" class="circle-button circle-sm circle-hb text-center bg-success text-light" title="微信" role="button" tabindex="0" aria-label="微信二维码">
    <i class="fa-brands fa-weixin t-md"></i>
  </div>
  <div data-id="qzone" class="share-to circle-button circle-sm circle-hb text-center bg-warning text-light" title="QQ空间">
    <i class="fa-brands fa-qq t-md"></i>
  </div>
  <div data-id="tw" class="share-to circle-button circle-sm circle-hb text-center bg-info text-light" title="Twitter">
    <i class="fa-brands fa-twitter t-md"></i>
  </div>
  <div data-id="fb" class="share-to circle-button circle-sm circle-hb text-center bg-primary text-light" title="Facebook">
    <i class="fa-brands fa-facebook t-md"></i>
  </div>
  <div id="${copyUid}" class="circle-button circle-sm circle-hb text-center bg-dark text-light" title="复制链接">
    <i class="fa-regular fa-copy t-md"></i>
  </div>
</div>
<div id="${qrBoxUid}" class="text-center mt15 d-none">
  <p class="text-center t-sm mb-1 mt-1">使用微信扫一扫</p>
  <div class="d-flex justify-content-center">
    <div id="${qrUid}"></div>
  </div>
  <div class="c-sub fs12 mt5">长按识别二维码阅读</div>
</div>
            `.trim();

            this.modalLoadRender(dataId, html, "分享", false, false);

            const renderWxQr = async () => {
                try {
                    await this.ensureQRCodeReady(5000);
                    const qrEl = document.getElementById(qrUid);
                    if (qrEl) {
                        qrEl.innerHTML = "";
                        // eslint-disable-next-line no-undef
                        new QRCode(qrEl, {
                            text: url,
                            width: 120,
                            height: 120,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: (typeof QRCode !== "undefined" && QRCode.CorrectLevel) ? QRCode.CorrectLevel.M : undefined,
                        });
                    }
                } catch (err) {
                    console.error(err);
                    this.toast("二维码组件未加载", TYPE_WARNING);
                }
            };

            const wxEl = document.getElementById(wxUid);
            const qrBoxEl = document.getElementById(qrBoxUid);
            if (wxEl && qrBoxEl) {
                wxEl.onclick = async () => {
                    const isHidden = qrBoxEl.classList.contains("d-none");
                    if (isHidden) {
                        qrBoxEl.classList.remove("d-none");
                        if (!qrBoxEl.dataset.rendered) {
                            await renderWxQr();
                            qrBoxEl.dataset.rendered = "1";
                        }
                        return;
                    }
                    qrBoxEl.classList.add("d-none");
                };
                wxEl.onkeydown = (e) => {
                    if (e.key === "Enter" || e.key === " ") {
                        e.preventDefault();
                        wxEl.click();
                    }
                };
            }

            const copyEl = document.getElementById(copyUid);
            if (copyEl) {
                copyEl.onclick = async () => {
                    try {
                        await this.copyToClipboard(url);
                        this.toast("复制链接成功", TYPE_SUCCESS);
                    } catch (err) {
                        console.error(err);
                        this.toast("复制失败", TYPE_DANGER);
                    }
                };
            }
        });

        $(document).off("keydown", ".share-modal-open");
        $(document).on("keydown", ".share-modal-open", (e) => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                $(e.currentTarget).trigger("click");
            }
        });
    }

    copyToClipboard(text) {
        const val = (text ?? "").toString();
        if (val === "") return Promise.reject(new Error("empty text"));
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(val);
        }
        return new Promise((resolve, reject) => {
            try {
                const textarea = document.createElement("textarea");
                textarea.value = val;
                textarea.setAttribute("readonly", "readonly");
                textarea.style.position = "fixed";
                textarea.style.top = "-9999px";
                textarea.style.left = "-9999px";
                document.body.appendChild(textarea);
                textarea.select();
                const ok = document.execCommand("copy");
                document.body.removeChild(textarea);
                ok ? resolve() : reject(new Error("execCommand failed"));
            } catch (e) {
                reject(e);
            }
        });
    }

    ensureQRCodeReady(timeoutMs = 5000) {
        if (typeof window.QRCode !== "undefined") return Promise.resolve();
        return new Promise((resolve, reject) => {
            const start = Date.now();
            const tick = () => {
                if (typeof window.QRCode !== "undefined") return resolve();
                if (Date.now() - start > timeoutMs) return reject(new Error("QRCode load timeout"));
                setTimeout(tick, 50);
            };
            tick();
        });
    }

    waitImagesLoaded(rootEl, timeoutMs = 3500) {
        const imgs = Array.from(rootEl.querySelectorAll("img")).filter(img => !!img.src);
        if (imgs.length === 0) return Promise.resolve();
        const start = Date.now();
        return new Promise((resolve) => {
            const done = () => resolve();
            const check = () => {
                const allDone = imgs.every(img => img.complete);
                if (allDone) return done();
                if (Date.now() - start > timeoutMs) return done();
                setTimeout(check, 80);
            };
            check();
        });
    }

    sidebarMenuEventInit() {
        let currentOpenSubMenu = null;
        $(document).on("touchend", ".post-menu-toggle", (e) => {
            e.preventDefault();
            this.toggleMenu();
        });
        $(document).on("click", ".post-menu-toggle", () => {
            this.toggleMenu();
        });
        $(document).on("click", ".post-menu-item", (e) => {
            const el = $(this.ct(e))
            const id = el.attr("data-id")
            if (currentOpenSubMenu) {
                const parentUl = el.parents("ul")
                let curClass = "post-menu-sub-" + currentOpenSubMenu
                while (true) {
                    if (typeof (curClass) === "undefined") {
                        break
                    }
                    const currentMenu = $("." + curClass)
                    const classStr = currentMenu.attr("class")
                    const und = typeof (classStr) == "undefined"
                    if (und || parentUl.attr("class") === currentMenu.attr("class")) {
                        break;
                    } else {
                        currentMenu.hide();
                        curClass = currentMenu.parents("ul").attr("class");
                    }
                }
            }
            const subMenu = $(".post-menu-sub-" + id)
            if (subMenu.length > 0) {
                subMenu.show()
                currentOpenSubMenu = id
            }
        });
        $(document).on("click", ".pk-menu-to", (e) => {
            const to = $(this.ct(e)).attr("href");
            const headerHeight = $("#header").innerHeight();
            $("html, body").stop().animate({
                scrollTop: ($(to).offset().top - headerHeight - 10) + "px"
            }, {
                duration: 50,
                easing: "swing"
            });
            if(!this.data.pc){
                this.toggleMenu()
            }
            return false;
        });
    }

    toggleMenu() {
        const menuContainer = $("#post-menus");
        const menuButton = $("#post-menu-state");
        const className = "data-open";
        const isOpen = menuButton.hasClass(className);
        
        if (isOpen) {
            // 关闭菜单
            menuContainer.removeClass("show");
            menuButton.removeClass(className);
        } else {
            // 打开菜单
            menuContainer.addClass("show");
            menuButton.addClass(className);
        }
    }

    lazyLoadInit(parent = null, el = '.lazy') {
        if (window.lozad) {
            const observer = lozad([el, 'img[data-lazy="true"]'], {
                rootMargin: '10px 0px',
                threshold: 0.1,
                enableAutoReload: true,
                load: (el) => {
                    el.classList.add('loaded');
                    el.src = el.getAttribute('data-src');
                }
            });
            observer.observe();
        }
    }

    loadParams() {
        this.data.params = puock_metas;
    }

    initReadProgress() {
        const readProgress = $("#page-read-progress .progress-bar");
        document.addEventListener('scroll', () => {
            const a = window.scrollY / (document.documentElement.scrollHeight - window.innerHeight) * 100;
            readProgress.attr("style", "width:" + a.toFixed(0) + "%");
        });
    }

    tooltipInit(el = $("[data-bs-toggle=\"tooltip\"]")) {
        [...el].map(tooltipTriggerEl => {
            new bootstrap.Tooltip(tooltipTriggerEl, {
                placement: 'bottom', trigger: 'hover'
            })
        })
    }

    pageChangeInit() {
        this.initReadProgress()
        this.modeInit();
        this.loadCommentInfo();
        this.katexParse();
        this.initCodeHighlight();
        this.pageLinkBlankOpenInit()
        this.initGithubCard();
        this.keyUpHandle();
        this.swiperInit();
        this.rippleInit();
        if (this.data.params.use_post_menu) {
            this.generatePostMenuHTML()
        }
        this.tooltipInit()
        if(!this.data.params.off_img_viewer){
            const entryContent = jQuery(".entry-content");
            entryContent.viewer('destroy');
            entryContent.viewer({
                navbar: false,
                url: (image) => {
                    if (!image || !image.getAttribute) return '';
                    const src = (image.getAttribute('src') || '').trim();
                    const dataSrc = (image.getAttribute('data-src') || '').trim();
                    if (this.data.params.main_lazy_img) {
                        return dataSrc || src;
                    }
                    return src || dataSrc;
                }
            });
        }
        const cp = new ClipboardJS('.pk-copy', {
            text: (trigger) => {
                const t = $(trigger)
                let input = t.attr("data-cp-input")
                let el = t.attr("data-cp-el")
                let val = t.attr("data-cp-val")
                let func = t.attr("data-cp-func")
                let text;
                if(typeof func !=="undefined"){
                    text = window[func](t)
                }else if (typeof val !== "undefined") {
                    text = val
                } else if (typeof input !== "undefined") {
                    text = $(input).val()
                } else if (typeof el !== "undefined") {
                    text = $(el).text()
                } else {
                    text = t.text()
                }
                return text;
            },
        });
        cp.on("success", (e) => {
            let name = $(e.trigger).attr('data-cp-title') || "";
            this.toast(`复制${name}成功`)
        })
        cp.on("error", (e) => {
            let name = $(e.trigger).attr('data-cp-title') || "";
            this.toast(`复制${name}失败`, TYPE_DANGER)
        })
        this.lazyLoadInit()
       // $('#post-main, #sidebar').theiaStickySidebar({
       //     additionalMarginTop: 20
       // });
        // 新增：pjax切换后重新初始化评论相关事件
        this.initCommentEvents();
        
        // PJAX修复：确保评论功能完全重新初始化
        if (this.data.params.is_pjax) {
            // 强制重新加载评论信息
            this.loadCommentInfo();
            // 重置提交状态 - 修复第二次评论卡住的问题
            this.data.comment.submitting = false;
            // 重新绑定所有评论相关事件
            setTimeout(() => {
                this.eventCommentPreSubmit();
                this.eventCommentPageChangeEvent();
                this.eventOpenCommentBox();
                this.eventCloseCommentBox();
                this.eventSmiley();
            }, 200);
        }
    }


    getPostMenuStructure() {
        $('.entry-content').find('h1,h2,h3,h4,h5,h6').each(function(index, el) {
        if (!$(el).attr('id')) {
        let safeText = $(el).text().trim().replace(/\s+/g, '-').replace(/[^\w\-]/g, '').toLowerCase();
        // 防止重复id
        let uniqId = safeText || `heading-${index}`;
        // 如果已经有相同id，添加序号
        let counter = 1;
        while($('#' + uniqId).length > 0) {
            uniqId = `${safeText}-${counter++}`;
        }
        $(el).attr('id', uniqId);
       }
    });
        let res = []
        for (let item of $(".entry-content").find('h1,h2,h3,h4,h5,h6')) {
            res.push({name: $(item).text().trim(), level: item.tagName.toLowerCase(), id: $(item).attr("id")})
        }
        return res
    }

    generatePostMenuHTML() {
        const menus = this.getPostMenuStructure();
        if (menus.length > 0) {
            let result = "<ul>";
            if (menus.length > 0) {
                const finalMenus = []
                let maxLevel = 6;
                const initChildren = (item) => {
                    item.children = []
                    return item
                }
                const getLevel = (item) => {
                    item.levelInt = parseInt(item.level.replace("h", ""))
                    if (item.levelInt < maxLevel) {
                        maxLevel = item.levelInt
                    }
                    return item.levelInt
                }
                const firstMenu = initChildren(menus[0])
                const firstLevel = getLevel(firstMenu)
                let loadIndex = 0;
                const eqLevelFn = (unMenu, parentMen) => {
                    const nextUnMenu = loadMenu(unMenu, parentMen)
                    if (nextUnMenu != null) {
                        if (getLevel(nextUnMenu) === getLevel(unMenu)) {
                            return eqLevelFn(nextUnMenu, parentMen)
                        }
                    }
                    return nextUnMenu;
                }
                const loadMenu = (menu, parentMenu) => {
                    if (loadIndex >= menus.length - 1) {
                        return null;
                    }
                    const nextIndex = ++loadIndex;
                    const nextMenu = initChildren(menus[nextIndex])
                    const nowLevel = getLevel(menu)
                    const nextLevel = getLevel(nextMenu)
                    let unknownMenu = null;
                    if (nextLevel === firstLevel) {
                        finalMenus.push(nextMenu)
                        unknownMenu = loadMenu(nextMenu, null)
                    } else if (nextLevel > nowLevel) {
                        menu.children.push(nextMenu)
                        unknownMenu = loadMenu(nextMenu, menu)
                    } else if (nextLevel === nowLevel && parentMenu != null) {
                        parentMenu.children.push(nextMenu)
                        unknownMenu = loadMenu(nextMenu, parentMenu)
                    } else {
                        return nextMenu
                    }
                    if (unknownMenu != null) {
                        const unknownLevel = getLevel(unknownMenu)
                        if (unknownLevel === nowLevel) {
                            parentMenu.children.push(unknownMenu)
                            unknownMenu = eqLevelFn(unknownMenu, parentMenu)
                        }
                    }
                    return unknownMenu
                }
                finalMenus.push(firstMenu)
                while (true) {
                    const unknownMenu = loadMenu(firstMenu, null)
                    if (unknownMenu == null) {
                        break
                    }
                    loadMenu(unknownMenu, null)
                }
                let menuIndex = 0;
                const outHtml = (item, parent) => {
                    ++menuIndex;
                    const id = menuIndex;
                    const pl = (item.levelInt - maxLevel) * 10
                    let out = `<li data-level="${item.levelInt}" style='padding-left:${pl}px'>`
                    out += `<a class='pk-menu-to a-link t-w-400 t-md post-menu-item' data-parent="${parent}" data-id="${id}" href='#${item.id}'><i class='fa ${item.children.length > 0 ? 'fa-angle-right' : 'fa-file-invoice'} t-sm c-sub mr-1'></i> ${item.name}</a>`
                    if (item.children.length > 0) {
                        out += `<ul class="post-menu-sub-${id}" data-parent="${parent + 1}">`
                        for (let child of item.children) {
                            out += outHtml(child, id)
                        }
                        out += `</ul>`
                    }
                    out += "</li>"
                    return out;
                }
                finalMenus.forEach(item => {
                    result += outHtml(item, menuIndex)
                })
            }
            result += "</ul>"
            $("#post-menu-content-items").html(result);
            $(".post-menus-box").show();
        }
    }

    initCodeHighlight(fullChange = true, bodyEl="body") {
        if(this.data.params.off_code_highlighting){
            return
        }
        if (window.hljs !== undefined) {
            window.hljs.configure({ignoreUnescapedHTML: true})
            $(bodyEl).find("pre").each((index, block) => {
                const el = $(block);
                const codeChildClass = el.children("code") ? el.children("code").attr("class") : undefined;
                if (codeChildClass) {
                    if (codeChildClass.indexOf("katex") !== -1 || codeChildClass.indexOf("latex") !== -1 || codeChildClass.indexOf("flowchart") !== -1
                        || codeChildClass.indexOf("flow") !== -1 || codeChildClass.indexOf("seq") !== -1 || codeChildClass.indexOf("math") !== -1) {
                        return;
                    }
                }
                if (!el.attr("id")) {
                    el.attr("id", "hljs-item-" + index)
                    el.before("<div class='pk-code-tools' data-pre-id='hljs-item-" + index + "'><div class='dot'>" +
                        "<i></i><i></i><i></i></div><div class='actions'><div><i class='i fa fa-copy cp-code' data-clipboard-target='#hljs-item-" + index + "'></i></div></div></div>")
                    if (window.hljs.highlightElement) {
                        window.hljs.highlightElement(block);
                    } else {
                        window.hljs.highlightBlock(block);
                    }
                    window.hljs.lineNumbersBlock(block);
                }
            });
            if (fullChange) {
                const cp = new ClipboardJS('.cp-code');
                cp.on("success", (e) => {
                    e.clearSelection();
                    this.toast('已复制到剪切板')
                })
            }
        }
    }

    localstorageToggle(name, val = null) {
        return val != null ? localStorage.setItem(name, val) : localStorage.getItem(name);
    }

    loadCommentInfo() {
        const authorText = this.localstorageToggle("comment_author"),
            emailText = this.localstorageToggle("comment_email"),
            urlText = this.localstorageToggle("comment_url");
        if (authorText != null && emailText != null) {
            $("#comment_author").val(authorText);
            $("#comment_email").val(emailText);
            $("#comment_url").val(urlText);
        }
    }

    setCommentInfo() {
        this.localstorageToggle("comment_author", $("#comment_author").val());
        this.localstorageToggle("comment_email", $("#comment_email").val());
        this.localstorageToggle("comment_url", $("#comment_url").val());
    }

    modeInit() {
        this.modeChange();
    }

    modeChange(toLight = null, isSwitch = false) {
        const body = $("body");
        if (typeof (toLight) === "string") {
            toLight = toLight === 'true';
        }
        let mode = Cookies.get('mode') || 'auto'
        if (toLight === null) {
            toLight = mode==='light';
            if(mode==='auto'){
                toLight = !window.matchMedia('(prefers-color-scheme:dark)').matches
            }
        }
        if (isSwitch) {
            if(mode==='light'){
                mode = 'dark'
                toLight = false;
            }else if(mode==='dark'){
                mode = 'auto'
                toLight = !window.matchMedia('(prefers-color-scheme:dark)').matches;
            }else{
                mode = 'light'
                toLight = true;
            }
            console.log(mode, toLight)
        }
        let dn = 'd-none';
        if (toLight) {
            $("#logo-light").removeClass(dn);
            $("#logo-dark").addClass(dn);
        } else {
            $("#logo-dark").removeClass(dn);
            $("#logo-light").addClass(dn);
        }
        $(".colorMode").each((_, e) => {
            const el = $(e);
            let target;
            if (el.prop("localName") === 'i') {
                target = el;
            } else {
                target = $(el).find("i");
            }
            if (target) {
                target.removeClass("fa-sun").removeClass("fa-moon").removeClass('fa-circle-half-stroke')
                    .addClass(mode==='auto' ? 'fa-circle-half-stroke' : (mode==='light' ? "fa-sun" : "fa-moon"));
            }
        })
        body.removeClass(this.data.tag + "-auto")
        body.removeClass(toLight ? this.data.tag + "-dark" : this.data.tag + "-light");
        body.addClass(toLight ? this.data.tag + "-light" : this.data.tag + "-dark");
        // this.localstorageToggle('light', toLight)
        Cookies.set('mode', mode)
    }

    modeChangeListener() {
        if(Cookies.get('mode')==='auto'){
            this.modeChange(!window.matchMedia('(prefers-color-scheme:dark)').matches);
        }
    }

    registerModeChangeEvent() {
        if (this.data.params.mode_switch) {
            try {
                window.matchMedia('(prefers-color-scheme:dark)').addEventListener('change', () => {
                    this.modeChangeListener()
                });
            } catch (ex) {
                window.matchMedia('(prefers-color-scheme:dark)').addListener(() => {
                    this.modeChangeListener()
                });
            }
        }
    }

    infoToastShow(text, title = '提示') {
        const infoToast = $('#infoToast');
        $("#infoToastTitle").html(title);
        $("#infoToastText").html(text);
        infoToast.modal('show');
    }

    registerMobileMenu() {
        const fn = (s) => {
            if (typeof (s) !== 'string') {
                s = 'Out'
            }
            $("#mobile-menu").attr("class", "animated fade" + s + "Left");
            $("#mobile-menu-backdrop").attr("class", "modal-backdrop animated fade" + s + "Right");
        }
        $(document).on("click", "#mobile-menu-backdrop", fn);
        $(document).on("click", ".mobile-menu-close", fn);
        $(document).on("click", ".mobile-menu-s", () => {
            fn('In');
        });
    }

    gotoArea(el, speed = 50) {
        const top = $(el).offset().top - $("#header").height() - 10;
        $('html,body').stop().animate({scrollTop: top}, speed);
    }

    pushAjaxCommentHistoryState(href) {
        history.pushState({foo: "bar"}, "page 2", href);
    }

    eventCommentPageChangeEvent() {
        $(document).off('click', '.comment-ajax-load a.page-numbers');
        $(document).on('click', '.comment-ajax-load a.page-numbers', (e) => {
            const postCommentsEl = $("#post-comments");
            const loadBox = $("#comment-ajax-load");
            $("#comment-cancel").click();
            let href = $(this.ct(e)).attr("href");
            this.pushAjaxCommentHistoryState(href);
            postCommentsEl.html(" ");
            this.gotoArea("#comments");
            loadBox.removeClass('d-none');
            $.post(href, {}, (data) => {
                postCommentsEl.html($(data).find("#post-comments"));
                loadBox.addClass('d-none');
                this.initCodeHighlight(false);
                this.lazyLoadInit(postCommentsEl);
            }).fail(() => {
                location = href;
            });
            return false;
        })

    }

    parseFormData(formEl, args = {}) {
        // 先获取表单所有字段
        const dataArr = formEl.serializeArray();
        const data = {};
        for (let i = 0; i < dataArr.length; i++) {
            data[dataArr[i].name] = dataArr[i].value;
        }
        // 合并额外参数
        return jQuery.param(Object.assign(data, args));
    }

    eventCommentPreSubmit() {
        $(document).off('submit', '#comment-form');
        $(document).on('submit', '#comment-form', (e) => {
            e.preventDefault();
            
            // 防重复提交检查
            if (this.data.comment.submitting) {
                this.toast('评论正在提交中，请稍候', TYPE_WARNING);
                return;
            }
            
            if ($("#comment-logged").val() === '0' && ($.trim($("#comment_author").val()) === '' || $.trim($("#comment_email").val()) === '')) {
                this.toast('评论信息不能为空', TYPE_WARNING);
                return;
            }
            if ($.trim($("#comment").val()) === '') {
                this.toast('评论内容不能为空', TYPE_WARNING);
                return;
            }
            if (this.data.params.vd_comment) {
                if (this.data.params.vd_type === 'img') {
                    if ($.trim($("#comment-vd").val()) === '') {
                        this.toast('验证码不能为空', TYPE_WARNING);
                        return;
                    }
                } else {
                    this.gt.validate((code) => {
                        this.commentSubmit(this.ct(e), code)
                    })
                    return;
                }
            }
            this.data.comment.submitting = true;
            this.commentSubmit(this.ct(e))
        })
    }

    commentSubmit(target, args = {}) {
     let submitUrl = $("#comment-form").attr("action");
     this.commentFormLoadStateChange(true);
     const el = $(target);
     
     // 获取当前回复的评论ID，用于后续定位
     const replyId = $("#comment_parent").val();
     
         $.ajax({
             url: submitUrl,
             data: this.parseFormData(el, args),
             type: el.attr('method'),
         success: (data, _textStatus, jqXHR) => {
             const hasCommentsWrap = $(data).find("#comments").length > 0;
             if (!hasCommentsWrap) {
                 this.commentFormLoadStateChange(false);
                 this.data.comment.submitting = false;
                 this.toast('评论提交结果异常，请刷新页面查看提示后重试', TYPE_DANGER);
                 return;
             }

             this.toast('评论已提交（如开启审核可能稍后显示）', TYPE_SUCCESS);
             $("#comment-vd").val("");
             $("#comment").val("");
             
             // 重置提交状态 - 修复第二次评论卡住的问题
             this.data.comment.submitting = false;
             this.commentFormLoadStateChange(false);
             
             // PJAX修复：强制重新加载整个页面以确保评论状态正确
             if (this.data.params.is_pjax) {
                 // 构建带有评论锚点的URL
                 let targetUrl = window.location.href.split('#')[0];
                if (replyId) {
                    // 如果是回复评论，定位到被回复的评论
                    targetUrl += '#comment-' + replyId;
                } else {
                    // 如果是新评论，定位到评论区顶部
                    targetUrl += '#comments';
                 }
                 
                 // 使用PJAX重新加载当前页面
                 InstantClick.go(targetUrl);
                 
                 // 延迟执行后续操作，等待页面加载完成
                 setTimeout(() => {
                     if (replyId) {
                         // 滚动到被回复的评论
                        this.gotoArea('#comment-' + replyId);
                    } else {
                        // 滚动到评论区
                        this.gotoArea("#comments");
                    }
                }, 500);
             } else {
                 // 非PJAX模式下使用传统方式
                 // 获取整个评论区域的新内容
                 const newCommentsEl = $(data).find("#comments");
                 if (!newCommentsEl.length) {
                     this.toast('评论提交失败，请刷新页面后重试', TYPE_DANGER);
                     return;
                 }
                 const newComments = newCommentsEl.html();
                 
                 // 替换当前评论区域
                 $("#comments").html(newComments);
                 
                 // 重置评论表单状态
                 $("#comment-form").trigger("reset");
                 $("#comment-cancel").click();
                 this.setCommentInfo();
                 
                 // 重新初始化相关组件
                 this.initCodeHighlight(false);
                 this.lazyLoadInit();
                this.tooltipInit();
                
                // 重新初始化评论相关事件
                this.initCommentEvents();
                
                // 滚动到指定位置
                if (replyId) {
                    // 如果是回复评论，定位到被回复的评论
                    this.gotoArea('#comment-' + replyId);
                } else {
                    // 如果是新评论，定位到评论区
                    this.gotoArea("#comments");
                }
            }
         },
         error: (res) => {
             this.commentFormLoadStateChange(false);
             this.data.comment.submitting = false;
             let msg = "评论提交失败";
             if (res.responseJSON && res.responseJSON.msg) {
                 msg = res.responseJSON.msg;
             } else if (res.responseText) {
                 try {
                     const doc = new DOMParser().parseFromString(res.responseText, 'text/html');
                     const text = (doc.querySelector('.container') || doc.body || {}).textContent;
                     if (text && text.trim()) {
                         msg = text.trim().replace(/\s+/g, ' ');
                     }
                 } catch (e) {
                     // ignore parse errors
                 }
             } else if (res.statusText) {
                 msg = "网络错误：" + res.statusText;
             }
             this.toast(msg, TYPE_DANGER);
         }
     });
 }

     commentFormLoadStateChange(forceLoading = null) {
         const commentSubmit = $("#comment-submit");
         const isLoading = forceLoading === null ? !this.data.comment.loading : !!forceLoading;
         this.data.comment.loading = isLoading;
         if (isLoading) {
             commentSubmit.html('<span class="spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>提交中...');
             commentSubmit.attr("disabled", true)
             return;
         }

         if (this.data.comment.val) {
             clearInterval(this.data.comment.val);
             this.data.comment.val = null;
         }
         commentSubmit.html("提交评论");
         commentSubmit.removeAttr("disabled");
     }

    eventOpenCommentBox() {
        $(document).off("click", ".comment-reply");
        $(document).on("click", ".comment-reply", function(e) {
            e.preventDefault();
            const replyBtn = $(e.currentTarget);
            const replyId = replyBtn.attr("data-coid");
            if ($.trim(replyId) === '') {
                window.Puock.toast('结构有误', TYPE_DANGER);
                return;
            }
            const cf = $("#comment-form");
            const commentLi = replyBtn.closest('.post-comment');
            // 只在表单在原位时插入占位符
            if (!$("#comment-form-place-holder").length && cf.parent().attr("id") === "comment-form-box") {
                cf.before('<div id="comment-form-place-holder"></div>');
            }
            // 每次都append到目标评论下方
            commentLi.append(cf);
            $("#comment-cancel").removeClass("d-none");
            $("#comment").val("");
            $("#comment_parent").val(replyId);
            window.Puock.data.comment.replyId = replyId;
            // 滚动至表单
            if (cf.length && cf[0].scrollIntoView) {
                cf[0].scrollIntoView({behavior: "smooth", block: "center"});
            }
        });
    }

    eventCloseCommentBox() {
        $(document).off("click", "#comment-cancel");
        $(document).on("click", "#comment-cancel", () => {
            const cf = $("#comment-form");
            const holder = $("#comment-form-place-holder");
            if (holder.length) {
                holder.before(cf);
                holder.remove();
            } else {
                $("#comment-form-box").append(cf);
            }
            $("#comment-cancel").addClass("d-none");
            this.data.comment.replyId = null;
            $("#comment_parent").val('');
        });
    }

    eventSendPostLike() {
    let lastSendTime = 0;
    let throttleTimeMs = 3000;
    $(document).on("click", "#post-like", (e) => {
        const currentTime = new Date().getTime();
        if (currentTime - lastSendTime < throttleTimeMs) {
            this.toast("操作过于频繁", TYPE_WARNING);
            return;
        }
        lastSendTime = currentTime;
        
        const vm = $(this.ct(e));
        let cid = vm.attr("data-id");
        
        // 发送 AJAX 请求到当前文章页面
        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                likeup: 1,
                cid: cid
            },
            dataType: 'json',
            success: (response) => {
                // 检查返回的数据格式并更新点赞数
                if (response.success) {
                    vm.find("span").html(response.likes);
                    vm.addClass("bg-primary text-light");
                    this.toast("点赞成功", TYPE_SUCCESS);
                } else {
                    this.toast(response.msg || "点赞失败", TYPE_WARNING);
                }
            },
            error: () => {
                this.toast('点赞异常', TYPE_DANGER);
            }
        });
    })
}

    eventSmiley() {
        $(document).off('click', '.smiley-img');
        $(document).on('click', '.smiley-img', (e) => {
            const comment = $("#comment");
            comment.val(comment.val() + ' ' + $(this.ct(e)).attr("data-id") + ' ');
            layer.closeAll();
        });
    }

    startLoading() {
        return layer.load(0, {
            shade: [0.5, '#000']
        })
    }

    stopLoading(id = null) {
        layer.close(id)
    }

    getRemoteHtmlNode(url, callback) {
        const loading = this.startLoading()
        $.ajax({
            url: url,
            type: 'GET',
            success: (res)=>{
                this.stopLoading(loading)
                callback(res)
            },
            error: (err)=> {
                console.error(err)
                this.stopLoading(loading)
                this.toast("获取内容节点数据失败", TYPE_DANGER)
            }
        })
    }

    initModalToggle() {
        $(document).on("click", ".pk-modal-toggle", (e) => {
            const el = $(this.ct(e));
            const noTitle = el.data("no-title") !== undefined;
            const noPadding = el.data("no-padding") !== undefined;
            const title = el.attr("title") || el.data("title") || '提示';
            const url = el.data("url");
            const onceLoad = el.data("once-load")

            // If this is an <a>, keep it crawlable but prevent navigation on normal clicks.
            if (url && e && e.preventDefault && e.button === 0 && !e.metaKey && !e.ctrlKey && !e.shiftKey && !e.altKey) {
                e.preventDefault();
            }
            
            // 检查 url 是否存在，避免 SparkMD5 错误
            if (!url) {
                console.warn('Modal toggle: url is undefined');
                return;
            }
            
            const id = SparkMD5.hash(url)
            if (onceLoad && this.data.modalStorage[id]) {
                this.modalLoadRender(id, this.data.modalStorage[id], title, noTitle, noPadding)
            } else {
                this.getRemoteHtmlNode(url, (res) => {
                    if (onceLoad) {
                        if (!this.data.modalStorage[id]) {
                            this.data.modalStorage[id] = res;
                        }
                    }
                    this.modalLoadRender(id, res, title, noTitle, noPadding)
                })
            }
        })
    }

    modalLoadRender(dataId, html, title, noTitle, noPadding) {
        const id = "pk-modal-" + dataId;
        layer.open({
            type: 1,
            title: noTitle ? false : title,
            content: `<div id="${id}" style='${noPadding ? '' : 'padding: 20px'}' class='fs14'>${html}</div>`,
            shadeClose: true,
        })
        const idEl = $("#" + id);
        this.lazyLoadInit(idEl);
        this.tooltipInit(idEl.find("[data-bs-toggle=\"tooltip\"]"));
    }

    eventPostMainBoxResize() {
        $(document).on("click", ".post-main-size", () => {
            const postMain = $("#post-main"),
                postSlider = $("#sidebar"),
                min = postMain.hasClass("col-lg-8");
            postMain.removeClass(min ? "col-lg-8" : "col-lg-12");
            postMain.addClass(min ? "col-lg-12" : "col-lg-8");
            min ? postSlider.removeClass("d-lg-block") : postSlider.addClass("d-lg-block");
        })
    }

    katexParse() {
        return;
        if (typeof katex !== 'undefined') {
            const ks = $(document).find(".language-katex");
            const kl = $(document).find(".language-inline");
            console.log(ks, kl)
            if (ks.length > 0) {
                ks.parent("pre").attr("style", "text-align: center; background: none;");
                ks.addClass("katex-container").removeClass("language-katex");
                $(".katex-container").each((_, v) => {
                    this.katexItemParse($(v))
                });
            }
            if (kl.length > 0) {
                kl.each((_, v) => {
                    this.katexItemParse($(v))
                });
            }
        }
    }

    katexItemParse(item) {
        const katexText = item.text();
        const el = item.get(0);
        if (item.parent("code").length === 0) {
            try {
                katex.render(katexText, el)
            } catch (err) {
                item.html("<span class='err'>" + err)
            }
        }
    }

    initGithubCard() {
        $.each($(".github-card"), (index, _el) => {
            const el = $(_el);
            const repo = el.attr("data-repo");
            if (repo) {
                $.get(`https://api.github.com/repos/${repo}`, (res) => {
                    const link_html = `class="hide-hover" href="${res.html_url}" target="_blank" rel="noreferrer"`;
                    el.html(`<div class="card-header"><i class="fa-brands fa-github"></i><a ${link_html}>${res.full_name}</a></div>
                    <div class="card-body">${res.description}</div>
                    <div class="card-footer">
                    <div class="row">
                    <div class="col-4"><i class="fa-regular fa-star"></i><a ${link_html}>${res.stargazers_count}</a></div>
                    <div class="col-4"><i class="fa-solid fa-code-fork"></i><a ${link_html}>${res.forks}</a></div>
                    <div class="col-4"><i class="fa-regular fa-eye"></i><a ${link_html}>${res.subscribers_count}</a></div>
                    </div>
                    </div>
                `);
                    el.addClass("loaded");
                }, 'json').fail((err) => {
                    el.html(`<div class="alert alert-danger"><i class="fa fa-warning"></i>&nbsp;请求Github项目详情异常：${repo}</div>`)
                });
            }
        })
    }

    keyUpHandle() {
        const prevOrNextEl = $(".single-next-or-pre")
        if (prevOrNextEl) {
            window.onkeyup = function (event) {
                if('BODY'===event.target?.tagName){
                    let url = null;
                    switch (event.key) {
                        case 'ArrowLeft': {
                            url = prevOrNextEl.find("a[rel='prev']").attr("href");
                            break
                        }
                        case 'ArrowRight': {
                            url = prevOrNextEl.find("a[rel='next']").attr("href");
                            break
                        }
                    }
                    if (url) {
                        window.location = url
                    }
                }
            }
        }
    }

    swiperInit() {
        $("[data-swiper='init']").each((_, _el) => {
            const el = $(_el);
            const swiperClass = el.attr("data-swiper-class");
            const elArgs = el.attr("data-swiper-args");
            let args = {}
            if (elArgs) {
                args = JSON.parse(elArgs)
            }
            new Swiper('.' + swiperClass, args);
        });
    }

    swiperOnceEvent() {
        $(document).on("click", ".swiper-slide a", (e) => {
            if (this.data.params.is_pjax) {
                e.preventDefault();
                this.goUrl(e.currentTarget.href)
            }
        });
    }

    toast(msg, type = TYPE_PRIMARY, options = {}) {
        options = Object.assign({
            duration: 2600,
            close: false,
            position: 'right',
            gravity: 'bottom',
            offset: {},
            className: 't-' + type,
        }, options)
        const t = Toastify({
            text: msg,
            ...options
        });
        t.showToast();
        return t;
    }

    // 新增：统一初始化评论相关事件
    initCommentEvents() {
        this.eventOpenCommentBox();
        this.eventCloseCommentBox();
        this.eventSmiley();
    }
}

jQuery(() => {
        if (window.$ === undefined) {
            window.$ = jQuery;
        }
        window.Puock = new Puock()
        window.Puock.onceInit()
    }
)
