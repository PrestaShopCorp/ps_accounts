function Be(t, n) {
  for (var e = 0; e < n.length; e++) {
    var r = n[e];
    r.enumerable = r.enumerable || !1, r.configurable = !0, "value" in r && (r.writable = !0), Object.defineProperty(t, r.key, r);
  }
}
function Fn(t, n, e) {
  return n && Be(t.prototype, n), e && Be(t, e), Object.defineProperty(t, "prototype", { writable: !1 }), t;
}
/*!
 * Splide.js
 * Version  : 4.0.8
 * License  : MIT
 * Copyright: 2022 Naotoshi Fujita
 */
var We = "(prefers-reduced-motion: reduce)", St = 1, Gn = 2, Nt = 3, Ct = 4, Wt = 5, qt = 6, Qt = 7, Un = {
  CREATED: St,
  MOUNTED: Gn,
  IDLE: Nt,
  MOVING: Ct,
  SCROLLING: Wt,
  DRAGGING: qt,
  DESTROYED: Qt
};
function ct(t) {
  t.length = 0;
}
function Et(t, n, e) {
  return Array.prototype.slice.call(t, n, e);
}
function z(t) {
  return t.bind.apply(t, [null].concat(Et(arguments, 1)));
}
var Ae = setTimeout, ye = function() {
};
function He(t) {
  return requestAnimationFrame(t);
}
function re(t, n) {
  return typeof n === t;
}
function Ft(t) {
  return !be(t) && re("object", t);
}
var Re = Array.isArray, rn = z(re, "function"), dt = z(re, "string"), ie = z(re, "undefined");
function be(t) {
  return t === null;
}
function an(t) {
  return t instanceof HTMLElement;
}
function Dt(t) {
  return Re(t) ? t : [t];
}
function nt(t, n) {
  Dt(t).forEach(n);
}
function Oe(t, n) {
  return t.indexOf(n) > -1;
}
function jt(t, n) {
  return t.push.apply(t, Dt(n)), t;
}
function vt(t, n, e) {
  t && nt(n, function(r) {
    r && t.classList[e ? "add" : "remove"](r);
  });
}
function it(t, n) {
  vt(t, dt(n) ? n.split(" ") : n, !0);
}
function Ht(t, n) {
  nt(n, t.appendChild.bind(t));
}
function Ce(t, n) {
  nt(t, function(e) {
    var r = (n || e).parentNode;
    r && r.insertBefore(e, n);
  });
}
function Gt(t, n) {
  return an(t) && (t.msMatchesSelector || t.matches).call(t, n);
}
function on(t, n) {
  var e = t ? Et(t.children) : [];
  return n ? e.filter(function(r) {
    return Gt(r, n);
  }) : e;
}
function Yt(t, n) {
  return n ? on(t, n)[0] : t.firstElementChild;
}
var te = Object.keys;
function _t(t, n, e) {
  if (t) {
    var r = te(t);
    r = e ? r.reverse() : r;
    for (var a = 0; a < r.length; a++) {
      var s = r[a];
      if (s !== "__proto__" && n(t[s], s) === !1)
        break;
    }
  }
  return t;
}
function Ut(t) {
  return Et(arguments, 1).forEach(function(n) {
    _t(n, function(e, r) {
      t[r] = n[r];
    });
  }), t;
}
function lt(t) {
  return Et(arguments, 1).forEach(function(n) {
    _t(n, function(e, r) {
      Re(e) ? t[r] = e.slice() : Ft(e) ? t[r] = lt({}, Ft(t[r]) ? t[r] : {}, e) : t[r] = e;
    });
  }), t;
}
function Ye(t, n) {
  Dt(n || te(t)).forEach(function(e) {
    delete t[e];
  });
}
function at(t, n) {
  nt(t, function(e) {
    nt(n, function(r) {
      e && e.removeAttribute(r);
    });
  });
}
function x(t, n, e) {
  Ft(n) ? _t(n, function(r, a) {
    x(t, a, r);
  }) : nt(t, function(r) {
    be(e) || e === "" ? at(r, n) : r.setAttribute(n, String(e));
  });
}
function It(t, n, e) {
  var r = document.createElement(t);
  return n && (dt(n) ? it(r, n) : x(r, n)), e && Ht(e, r), r;
}
function J(t, n, e) {
  if (ie(e))
    return getComputedStyle(t)[n];
  be(e) || (t.style[n] = "" + e);
}
function ee(t, n) {
  J(t, "display", n);
}
function un(t) {
  t.setActive && t.setActive() || t.focus({
    preventScroll: !0
  });
}
function et(t, n) {
  return t.getAttribute(n);
}
function Ke(t, n) {
  return t && t.classList.contains(n);
}
function Q(t) {
  return t.getBoundingClientRect();
}
function At(t) {
  nt(t, function(n) {
    n && n.parentNode && n.parentNode.removeChild(n);
  });
}
function sn(t) {
  return Yt(new DOMParser().parseFromString(t, "text/html").body);
}
function ot(t, n) {
  t.preventDefault(), n && (t.stopPropagation(), t.stopImmediatePropagation());
}
function cn(t, n) {
  return t && t.querySelector(n);
}
function De(t, n) {
  return n ? Et(t.querySelectorAll(n)) : [];
}
function ut(t, n) {
  vt(t, n, !1);
}
function Te(t) {
  return t.timeStamp;
}
function ft(t) {
  return dt(t) ? t : t ? t + "px" : "";
}
var Z = "splide", Pe = "data-" + Z;
function xt(t, n) {
  if (!t)
    throw new Error("[" + Z + "] " + (n || ""));
}
var Rt = Math.min, kt = Math.max, ne = Math.floor, zt = Math.ceil, q = Math.abs;
function fn(t, n, e) {
  return q(t - n) < e;
}
function Zt(t, n, e, r) {
  var a = Rt(n, e), s = kt(n, e);
  return r ? a < t && t < s : a <= t && t <= s;
}
function Mt(t, n, e) {
  var r = Rt(n, e), a = kt(n, e);
  return Rt(kt(r, t), a);
}
function Se(t) {
  return +(t > 0) - +(t < 0);
}
function Ie(t, n) {
  return nt(n, function(e) {
    t = t.replace("%s", "" + e);
  }), t;
}
function pe(t) {
  return t < 10 ? "0" + t : "" + t;
}
var Xe = {};
function kn(t) {
  return "" + t + pe(Xe[t] = (Xe[t] || 0) + 1);
}
function vn() {
  var t = [];
  function n(i, v, c, f) {
    a(i, v, function(u, _, l) {
      var m = "addEventListener" in u, E = m ? u.removeEventListener.bind(u, _, c, f) : u.removeListener.bind(u, c);
      m ? u.addEventListener(_, c, f) : u.addListener(c), t.push([u, _, l, c, E]);
    });
  }
  function e(i, v, c) {
    a(i, v, function(f, u, _) {
      t = t.filter(function(l) {
        return l[0] === f && l[1] === u && l[2] === _ && (!c || l[3] === c) ? (l[4](), !1) : !0;
      });
    });
  }
  function r(i, v, c) {
    var f, u = !0;
    return typeof CustomEvent == "function" ? f = new CustomEvent(v, {
      bubbles: u,
      detail: c
    }) : (f = document.createEvent("CustomEvent"), f.initCustomEvent(v, u, !1, c)), i.dispatchEvent(f), f;
  }
  function a(i, v, c) {
    nt(i, function(f) {
      f && nt(v, function(u) {
        u.split(" ").forEach(function(_) {
          var l = _.split(".");
          c(f, l[0], l[1]);
        });
      });
    });
  }
  function s() {
    t.forEach(function(i) {
      i[4]();
    }), ct(t);
  }
  return {
    bind: n,
    unbind: e,
    dispatch: r,
    destroy: s
  };
}
var ht = "mounted", $e = "ready", gt = "move", Kt = "moved", ln = "shifted", dn = "click", zn = "active", Bn = "inactive", Wn = "visible", Hn = "hidden", gn = "slide:keydown", K = "refresh", j = "updated", bt = "resize", En = "resized", Yn = "drag", Kn = "dragging", Xn = "dragged", we = "scroll", Pt = "scrolled", hn = "destroy", $n = "arrows:mounted", qn = "arrows:updated", jn = "pagination:mounted", Zn = "pagination:updated", mn = "navigation:mounted", _n = "autoplay:play", Jn = "autoplay:playing", An = "autoplay:pause", yn = "lazyload:loaded";
function H(t) {
  var n = t ? t.event.bus : document.createDocumentFragment(), e = vn();
  function r(s, i) {
    e.bind(n, Dt(s).join(" "), function(v) {
      i.apply(i, Re(v.detail) ? v.detail : []);
    });
  }
  function a(s) {
    e.dispatch(n, s, Et(arguments, 1));
  }
  return t && t.event.on(hn, e.destroy), Ut(e, {
    bus: n,
    on: r,
    off: z(e.unbind, n),
    emit: a
  });
}
function ae(t, n, e, r) {
  var a = Date.now, s, i = 0, v, c = !0, f = 0;
  function u() {
    if (!c) {
      if (i = t ? Rt((a() - s) / t, 1) : 1, e && e(i), i >= 1 && (n(), s = a(), r && ++f >= r))
        return l();
      He(u);
    }
  }
  function _(A) {
    !A && E(), s = a() - (A ? i * t : 0), c = !1, He(u);
  }
  function l() {
    c = !0;
  }
  function m() {
    s = a(), i = 0, e && e(i);
  }
  function E() {
    v && cancelAnimationFrame(v), i = 0, v = 0, c = !0;
  }
  function o(A) {
    t = A;
  }
  function g() {
    return c;
  }
  return {
    start: _,
    rewind: m,
    pause: l,
    cancel: E,
    set: o,
    isPaused: g
  };
}
function Qn(t) {
  var n = t;
  function e(a) {
    n = a;
  }
  function r(a) {
    return Oe(Dt(a), n);
  }
  return {
    set: e,
    is: r
  };
}
function tr(t, n) {
  var e;
  function r() {
    e || (e = ae(n || 0, function() {
      t(), e = null;
    }, null, 1), e.start());
  }
  return r;
}
function er(t, n, e) {
  var r = t.state, a = e.breakpoints || {}, s = e.reducedMotion || {}, i = vn(), v = [];
  function c() {
    var E = e.mediaQuery === "min";
    te(a).sort(function(o, g) {
      return E ? +o - +g : +g - +o;
    }).forEach(function(o) {
      u(a[o], "(" + (E ? "min" : "max") + "-width:" + o + "px)");
    }), u(s, We), _();
  }
  function f(E) {
    E && i.destroy();
  }
  function u(E, o) {
    var g = matchMedia(o);
    i.bind(g, "change", _), v.push([E, g]);
  }
  function _() {
    var E = r.is(Qt), o = e.direction, g = v.reduce(function(A, h) {
      return lt(A, h[1].matches ? h[0] : {});
    }, {});
    Ye(e), m(g), e.destroy ? t.destroy(e.destroy === "completely") : E ? (f(!0), t.mount()) : o !== e.direction && t.refresh();
  }
  function l(E) {
    matchMedia(We).matches && (E ? lt(e, s) : Ye(e, te(s)));
  }
  function m(E, o) {
    lt(e, E), o && lt(Object.getPrototypeOf(e), E), r.is(St) || t.emit(j, e);
  }
  return {
    setup: c,
    destroy: f,
    reduce: l,
    set: m
  };
}
var oe = "Arrow", ue = oe + "Left", se = oe + "Right", Tn = oe + "Up", Sn = oe + "Down", qe = "rtl", ce = "ttb", Ee = {
  width: ["height"],
  left: ["top", "right"],
  right: ["bottom", "left"],
  x: ["y"],
  X: ["Y"],
  Y: ["X"],
  ArrowLeft: [Tn, se],
  ArrowRight: [Sn, ue]
};
function nr(t, n, e) {
  function r(s, i, v) {
    v = v || e.direction;
    var c = v === qe && !i ? 1 : v === ce ? 0 : -1;
    return Ee[s] && Ee[s][c] || s.replace(/width|left|right/i, function(f, u) {
      var _ = Ee[f.toLowerCase()][c] || f;
      return u > 0 ? _.charAt(0).toUpperCase() + _.slice(1) : _;
    });
  }
  function a(s) {
    return s * (e.direction === qe ? 1 : -1);
  }
  return {
    resolve: r,
    orient: a
  };
}
var st = "role", Lt = "tabindex", rr = "disabled", rt = "aria-", Xt = rt + "controls", In = rt + "current", je = rt + "selected", tt = rt + "label", Me = rt + "labelledby", Ln = rt + "hidden", xe = rt + "orientation", Bt = rt + "roledescription", Ze = rt + "live", Je = rt + "busy", Qe = rt + "atomic", Ve = [st, Lt, rr, Xt, In, tt, Me, Ln, xe, Bt], he = Z, tn = Z + "__track", ir = Z + "__list", fe = Z + "__slide", Nn = fe + "--clone", ar = fe + "__container", Fe = Z + "__arrows", ve = Z + "__arrow", Rn = ve + "--prev", bn = ve + "--next", le = Z + "__pagination", On = le + "__page", or = Z + "__progress", ur = or + "__bar", sr = Z + "__toggle", cr = Z + "__spinner", fr = Z + "__sr", vr = "is-initialized", yt = "is-active", Cn = "is-prev", Dn = "is-next", Le = "is-visible", Ne = "is-loading", Pn = "is-focus-in", lr = [yt, Le, Cn, Dn, Ne, Pn], dr = {
  slide: fe,
  clone: Nn,
  arrows: Fe,
  arrow: ve,
  prev: Rn,
  next: bn,
  pagination: le,
  page: On,
  spinner: cr
};
function gr(t, n) {
  if (rn(t.closest))
    return t.closest(n);
  for (var e = t; e && e.nodeType === 1 && !Gt(e, n); )
    e = e.parentElement;
  return e;
}
var Er = 5, en = 200, pn = "touchstart mousedown", me = "touchmove mousemove", _e = "touchend touchcancel mouseup click";
function hr(t, n, e) {
  var r = H(t), a = r.on, s = r.bind, i = t.root, v = e.i18n, c = {}, f = [], u = [], _ = [], l, m, E;
  function o() {
    I(), O(), h();
  }
  function g() {
    a(K, A), a(K, o), a(j, h), s(document, pn + " keydown", function(N) {
      E = N.type === "keydown";
    }, {
      capture: !0
    }), s(i, "focusin", function() {
      vt(i, Pn, !!E);
    });
  }
  function A(N) {
    var D = Ve.concat("style");
    ct(f), ut(i, u), ut(l, _), at([l, m], D), at(i, N ? D : ["style", Bt]);
  }
  function h() {
    ut(i, u), ut(l, _), u = b(he), _ = b(tn), it(i, u), it(l, _), x(i, tt, e.label), x(i, Me, e.labelledby);
  }
  function I() {
    l = F("." + tn), m = Yt(l, "." + ir), xt(l && m, "A track/list element is missing."), jt(f, on(m, "." + fe + ":not(." + Nn + ")")), _t({
      arrows: Fe,
      pagination: le,
      prev: Rn,
      next: bn,
      bar: ur,
      toggle: sr
    }, function(N, D) {
      c[D] = F("." + N);
    }), Ut(c, {
      root: i,
      track: l,
      list: m,
      slides: f
    });
  }
  function O() {
    var N = i.id || kn(Z), D = e.role;
    i.id = N, l.id = l.id || N + "-track", m.id = m.id || N + "-list", !et(i, st) && i.tagName !== "SECTION" && D && x(i, st, D), x(i, Bt, v.carousel), x(m, st, "presentation");
  }
  function F(N) {
    var D = cn(i, N);
    return D && gr(D, "." + he) === i ? D : void 0;
  }
  function b(N) {
    return [N + "--" + e.type, N + "--" + e.direction, e.drag && N + "--draggable", e.isNavigation && N + "--nav", N === he && yt];
  }
  return Ut(c, {
    setup: o,
    mount: g,
    destroy: A
  });
}
var Ot = "slide", pt = "loop", de = "fade";
function mr(t, n, e, r) {
  var a = H(t), s = a.on, i = a.emit, v = a.bind, c = t.Components, f = t.root, u = t.options, _ = u.isNavigation, l = u.updateOnMove, m = u.i18n, E = u.pagination, o = u.slideFocus, g = c.Direction.resolve, A = et(r, "style"), h = et(r, tt), I = e > -1, O = Yt(r, "." + ar), F;
  function b() {
    I || (r.id = f.id + "-slide" + pe(n + 1), x(r, st, E ? "tabpanel" : "group"), x(r, Bt, m.slide), x(r, tt, h || Ie(m.slideLabel, [n + 1, t.length]))), N();
  }
  function N() {
    v(r, "click", z(i, dn, B)), v(r, "keydown", z(i, gn, B)), s([Kt, ln, Pt], y), s(mn, P), l && s(gt, M);
  }
  function D() {
    F = !0, a.destroy(), ut(r, lr), at(r, Ve), x(r, "style", A), x(r, tt, h || "");
  }
  function P() {
    var G = t.splides.map(function(S) {
      var C = S.splide.Components.Slides.getAt(n);
      return C ? C.slide.id : "";
    }).join(" ");
    x(r, tt, Ie(m.slideX, (I ? e : n) + 1)), x(r, Xt, G), x(r, st, o ? "button" : ""), o && at(r, Bt);
  }
  function M() {
    F || y();
  }
  function y() {
    if (!F) {
      var G = t.index;
      T(), p(), vt(r, Cn, n === G - 1), vt(r, Dn, n === G + 1);
    }
  }
  function T() {
    var G = U();
    G !== Ke(r, yt) && (vt(r, yt, G), x(r, In, _ && G || ""), i(G ? zn : Bn, B));
  }
  function p() {
    var G = w(), S = !G && (!U() || I);
    if (t.state.is([Ct, Wt]) || x(r, Ln, S || ""), x(De(r, u.focusableNodes || ""), Lt, S ? -1 : ""), o && x(r, Lt, S ? -1 : 0), G !== Ke(r, Le) && (vt(r, Le, G), i(G ? Wn : Hn, B)), !G && document.activeElement === r) {
      var C = c.Slides.getAt(t.index);
      C && un(C.slide);
    }
  }
  function R(G, S, C) {
    J(C && O || r, G, S);
  }
  function U() {
    var G = t.index;
    return G === n || u.cloneStatus && G === e;
  }
  function w() {
    if (t.is(de))
      return U();
    var G = Q(c.Elements.track), S = Q(r), C = g("left", !0), d = g("right", !0);
    return ne(G[C]) <= zt(S[C]) && ne(S[d]) <= zt(G[d]);
  }
  function X(G, S) {
    var C = q(G - n);
    return !I && (u.rewind || t.is(pt)) && (C = Rt(C, t.length - C)), C <= S;
  }
  var B = {
    index: n,
    slideIndex: e,
    slide: r,
    container: O,
    isClone: I,
    mount: b,
    destroy: D,
    update: y,
    style: R,
    isWithin: X
  };
  return B;
}
function _r(t, n, e) {
  var r = H(t), a = r.on, s = r.emit, i = r.bind, v = n.Elements, c = v.slides, f = v.list, u = [];
  function _() {
    l(), a(K, m), a(K, l), a([ht, K], function() {
      u.sort(function(y, T) {
        return y.index - T.index;
      });
    });
  }
  function l() {
    c.forEach(function(y, T) {
      o(y, T, -1);
    });
  }
  function m() {
    F(function(y) {
      y.destroy();
    }), ct(u);
  }
  function E() {
    F(function(y) {
      y.update();
    });
  }
  function o(y, T, p) {
    var R = mr(t, T, p, y);
    R.mount(), u.push(R);
  }
  function g(y) {
    return y ? b(function(T) {
      return !T.isClone;
    }) : u;
  }
  function A(y) {
    var T = n.Controller, p = T.toIndex(y), R = T.hasFocus() ? 1 : e.perPage;
    return b(function(U) {
      return Zt(U.index, p, p + R - 1);
    });
  }
  function h(y) {
    return b(y)[0];
  }
  function I(y, T) {
    nt(y, function(p) {
      if (dt(p) && (p = sn(p)), an(p)) {
        var R = c[T];
        R ? Ce(p, R) : Ht(f, p), it(p, e.classes.slide), D(p, z(s, bt));
      }
    }), s(K);
  }
  function O(y) {
    At(b(y).map(function(T) {
      return T.slide;
    })), s(K);
  }
  function F(y, T) {
    g(T).forEach(y);
  }
  function b(y) {
    return u.filter(rn(y) ? y : function(T) {
      return dt(y) ? Gt(T.slide, y) : Oe(Dt(y), T.index);
    });
  }
  function N(y, T, p) {
    F(function(R) {
      R.style(y, T, p);
    });
  }
  function D(y, T) {
    var p = De(y, "img"), R = p.length;
    R ? p.forEach(function(U) {
      i(U, "load error", function() {
        --R || T();
      });
    }) : T();
  }
  function P(y) {
    return y ? c.length : u.length;
  }
  function M() {
    return u.length > e.perPage;
  }
  return {
    mount: _,
    destroy: m,
    update: E,
    register: o,
    get: g,
    getIn: A,
    getAt: h,
    add: I,
    remove: O,
    forEach: F,
    filter: b,
    style: N,
    getLength: P,
    isEnough: M
  };
}
function Ar(t, n, e) {
  var r = H(t), a = r.on, s = r.bind, i = r.emit, v = n.Slides, c = n.Direction.resolve, f = n.Elements, u = f.root, _ = f.track, l = f.list, m = v.getAt, E = v.style, o, g;
  function A() {
    h(), s(window, "resize load", tr(z(i, bt))), a([j, K], h), a(bt, I);
  }
  function h() {
    g = null, o = e.direction === ce, J(u, "maxWidth", ft(e.width)), J(_, c("paddingLeft"), O(!1)), J(_, c("paddingRight"), O(!0)), I();
  }
  function I() {
    var w = Q(u);
    (!g || g.width !== w.width || g.height !== w.height) && (J(_, "height", F()), E(c("marginRight"), ft(e.gap)), E("width", N()), E("height", D(), !0), g = w, i(En));
  }
  function O(w) {
    var X = e.padding, B = c(w ? "right" : "left");
    return X && ft(X[B] || (Ft(X) ? 0 : X)) || "0px";
  }
  function F() {
    var w = "";
    return o && (w = b(), xt(w, "height or heightRatio is missing."), w = "calc(" + w + " - " + O(!1) + " - " + O(!0) + ")"), w;
  }
  function b() {
    return ft(e.height || Q(l).width * e.heightRatio);
  }
  function N() {
    return e.autoWidth ? null : ft(e.fixedWidth) || (o ? "" : P());
  }
  function D() {
    return ft(e.fixedHeight) || (o ? e.autoHeight ? null : P() : b());
  }
  function P() {
    var w = ft(e.gap);
    return "calc((100%" + (w && " + " + w) + ")/" + (e.perPage || 1) + (w && " - " + w) + ")";
  }
  function M() {
    return Q(l)[c("width")];
  }
  function y(w, X) {
    var B = m(w || 0);
    return B ? Q(B.slide)[c("width")] + (X ? 0 : R()) : 0;
  }
  function T(w, X) {
    var B = m(w);
    if (B) {
      var G = Q(B.slide)[c("right")], S = Q(l)[c("left")];
      return q(G - S) + (X ? 0 : R());
    }
    return 0;
  }
  function p() {
    return T(t.length - 1, !0) - T(-1, !0);
  }
  function R() {
    var w = m(0);
    return w && parseFloat(J(w.slide, c("marginRight"))) || 0;
  }
  function U(w) {
    return parseFloat(J(_, c("padding" + (w ? "Right" : "Left")))) || 0;
  }
  return {
    mount: A,
    listSize: M,
    slideSize: y,
    sliderSize: p,
    totalSize: T,
    getPadding: U
  };
}
var yr = 2;
function Tr(t, n, e) {
  var r = H(t), a = r.on, s = r.emit, i = n.Elements, v = n.Slides, c = n.Direction.resolve, f = [], u;
  function _() {
    l(), a(K, m), a(K, l), a([j, bt], E);
  }
  function l() {
    (u = A()) && (o(u), s(bt));
  }
  function m() {
    At(f), ct(f);
  }
  function E() {
    u < A() && s(K);
  }
  function o(h) {
    var I = v.get().slice(), O = I.length;
    if (O) {
      for (; I.length < h; )
        jt(I, I);
      jt(I.slice(-h), I.slice(0, h)).forEach(function(F, b) {
        var N = b < h, D = g(F.slide, b);
        N ? Ce(D, I[0].slide) : Ht(i.list, D), jt(f, D), v.register(D, b - h + (N ? 0 : O), F.index);
      });
    }
  }
  function g(h, I) {
    var O = h.cloneNode(!0);
    return it(O, e.classes.clone), O.id = t.root.id + "-clone" + pe(I + 1), O;
  }
  function A() {
    var h = e.clones;
    if (!t.is(pt))
      h = 0;
    else if (!h) {
      var I = e[c("fixedWidth")] && n.Layout.slideSize(0), O = I && zt(Q(i.track)[c("width")] / I);
      h = O || e[c("autoWidth")] && t.length || e.perPage * yr;
    }
    return h;
  }
  return {
    mount: _,
    destroy: m
  };
}
function Sr(t, n, e) {
  var r = H(t), a = r.on, s = r.emit, i = t.state.set, v = n.Layout, c = v.slideSize, f = v.getPadding, u = v.totalSize, _ = v.listSize, l = v.sliderSize, m = n.Direction, E = m.resolve, o = m.orient, g = n.Elements, A = g.list, h = g.track, I;
  function O() {
    I = n.Transition, a([ht, En, j, K], F);
  }
  function F() {
    n.Controller.isBusy() || (n.Scroll.cancel(), N(t.index), n.Slides.update());
  }
  function b(S, C, d, V) {
    S !== C && B(S > d) && (y(), D(M(R(), S > d), !0)), i(Ct), s(gt, C, d, S), I.start(C, function() {
      i(Nt), s(Kt, C, d, S), V && V();
    });
  }
  function N(S) {
    D(p(S, !0));
  }
  function D(S, C) {
    if (!t.is(de)) {
      var d = C ? S : P(S);
      J(A, "transform", "translate" + E("X") + "(" + d + "px)"), S !== d && s(ln);
    }
  }
  function P(S) {
    if (t.is(pt)) {
      var C = T(S), d = C > n.Controller.getEnd(), V = C < 0;
      (V || d) && (S = M(S, d));
    }
    return S;
  }
  function M(S, C) {
    var d = S - X(C), V = l();
    return S -= o(V * (zt(q(d) / V) || 1)) * (C ? 1 : -1), S;
  }
  function y() {
    D(R()), I.cancel();
  }
  function T(S) {
    for (var C = n.Slides.get(), d = 0, V = 1 / 0, Y = 0; Y < C.length; Y++) {
      var k = C[Y].index, $ = q(p(k, !0) - S);
      if ($ <= V)
        V = $, d = k;
      else
        break;
    }
    return d;
  }
  function p(S, C) {
    var d = o(u(S - 1) - w(S));
    return C ? U(d) : d;
  }
  function R() {
    var S = E("left");
    return Q(A)[S] - Q(h)[S] + o(f(!1));
  }
  function U(S) {
    return e.trimSpace && t.is(Ot) && (S = Mt(S, 0, o(l() - _()))), S;
  }
  function w(S) {
    var C = e.focus;
    return C === "center" ? (_() - c(S, !0)) / 2 : +C * c(S) || 0;
  }
  function X(S) {
    return p(S ? n.Controller.getEnd() : 0, !!e.trimSpace);
  }
  function B(S) {
    var C = o(M(R(), S));
    return S ? C >= 0 : C <= A[E("scrollWidth")] - Q(h)[E("width")];
  }
  function G(S, C) {
    C = ie(C) ? R() : C;
    var d = S !== !0 && o(C) < o(X(!1)), V = S !== !1 && o(C) > o(X(!0));
    return d || V;
  }
  return {
    mount: O,
    move: b,
    jump: N,
    translate: D,
    shift: M,
    cancel: y,
    toIndex: T,
    toPosition: p,
    getPosition: R,
    getLimit: X,
    exceededLimit: G,
    reposition: F
  };
}
function Ir(t, n, e) {
  var r = H(t), a = r.on, s = n.Move, i = s.getPosition, v = s.getLimit, c = s.toPosition, f = n.Slides, u = f.isEnough, _ = f.getLength, l = t.is(pt), m = t.is(Ot), E = z(M, !1), o = z(M, !0), g = e.start || 0, A = g, h, I, O;
  function F() {
    b(), a([j, K], b);
  }
  function b() {
    h = _(!0), I = e.perMove, O = e.perPage;
    var d = Mt(g, 0, h - 1);
    d !== g && (g = d, s.reposition());
  }
  function N(d, V, Y) {
    if (!C()) {
      var k = P(d), $ = p(k);
      $ > -1 && (V || $ !== g) && (B($), s.move(k, $, A, Y));
    }
  }
  function D(d, V, Y, k) {
    n.Scroll.scroll(d, V, Y, function() {
      B(p(s.toIndex(i()))), k && k();
    });
  }
  function P(d) {
    var V = g;
    if (dt(d)) {
      var Y = d.match(/([+\-<>])(\d+)?/) || [], k = Y[1], $ = Y[2];
      k === "+" || k === "-" ? V = y(g + +("" + k + (+$ || 1)), g) : k === ">" ? V = $ ? U(+$) : E(!0) : k === "<" && (V = o(!0));
    } else
      V = l ? d : Mt(d, 0, R());
    return V;
  }
  function M(d, V) {
    var Y = I || (S() ? 1 : O), k = y(g + Y * (d ? -1 : 1), g, !(I || S()));
    return k === -1 && m && !fn(i(), v(!d), 1) ? d ? 0 : R() : V ? k : p(k);
  }
  function y(d, V, Y) {
    if (u()) {
      var k = R(), $ = T(d);
      $ !== d && (V = d, d = $, Y = !1), d < 0 || d > k ? !I && (Zt(0, d, V, !0) || Zt(k, V, d, !0)) ? d = U(w(d)) : l ? d = Y ? d < 0 ? -(h % O || O) : h : d : e.rewind ? d = d < 0 ? k : 0 : d = -1 : Y && d !== V && (d = U(w(V) + (d < V ? -1 : 1)));
    } else
      d = -1;
    return d;
  }
  function T(d) {
    if (m && e.trimSpace === "move" && d !== g)
      for (var V = i(); V === c(d, !0) && Zt(d, 0, t.length - 1, !e.rewind); )
        d < g ? --d : ++d;
    return d;
  }
  function p(d) {
    return l ? (d + h) % h || 0 : d;
  }
  function R() {
    return kt(h - (S() || l && I ? 1 : O), 0);
  }
  function U(d) {
    return Mt(S() ? d : O * d, 0, R());
  }
  function w(d) {
    return S() ? d : ne((d >= R() ? h - 1 : d) / O);
  }
  function X(d) {
    var V = s.toIndex(d);
    return m ? Mt(V, 0, R()) : V;
  }
  function B(d) {
    d !== g && (A = g, g = d);
  }
  function G(d) {
    return d ? A : g;
  }
  function S() {
    return !ie(e.focus) || e.isNavigation;
  }
  function C() {
    return t.state.is([Ct, Wt]) && !!e.waitForTransition;
  }
  return {
    mount: F,
    go: N,
    scroll: D,
    getNext: E,
    getPrev: o,
    getAdjacent: M,
    getEnd: R,
    setIndex: B,
    getIndex: G,
    toIndex: U,
    toPage: w,
    toDest: X,
    hasFocus: S,
    isBusy: C
  };
}
var Lr = "http://www.w3.org/2000/svg", Nr = "m15.5 0.932-4.3 4.38 14.5 14.6-14.5 14.5 4.3 4.4 14.6-14.6 4.4-4.3-4.4-4.4-14.6-14.6z", $t = 40;
function Rr(t, n, e) {
  var r = H(t), a = r.on, s = r.bind, i = r.emit, v = e.classes, c = e.i18n, f = n.Elements, u = n.Controller, _ = f.arrows, l = f.track, m = _, E = f.prev, o = f.next, g, A, h = {};
  function I() {
    F(), a(j, O);
  }
  function O() {
    b(), I();
  }
  function F() {
    var T = e.arrows;
    T && !(E && o) && P(), E && o && (Ut(h, {
      prev: E,
      next: o
    }), ee(m, T ? "" : "none"), it(m, A = Fe + "--" + e.direction), T && (N(), y(), x([E, o], Xt, l.id), i($n, E, o)));
  }
  function b() {
    r.destroy(), ut(m, A), g ? (At(_ ? [E, o] : m), E = o = null) : at([E, o], Ve);
  }
  function N() {
    a([Kt, K, Pt], y), s(o, "click", z(D, ">")), s(E, "click", z(D, "<"));
  }
  function D(T) {
    u.go(T, !0);
  }
  function P() {
    m = _ || It("div", v.arrows), E = M(!0), o = M(!1), g = !0, Ht(m, [E, o]), !_ && Ce(m, l);
  }
  function M(T) {
    var p = '<button class="' + v.arrow + " " + (T ? v.prev : v.next) + '" type="button"><svg xmlns="' + Lr + '" viewBox="0 0 ' + $t + " " + $t + '" width="' + $t + '" height="' + $t + '" focusable="false"><path d="' + (e.arrowPath || Nr) + '" />';
    return sn(p);
  }
  function y() {
    var T = t.index, p = u.getPrev(), R = u.getNext(), U = p > -1 && T < p ? c.last : c.prev, w = R > -1 && T > R ? c.first : c.next;
    E.disabled = p < 0, o.disabled = R < 0, x(E, tt, U), x(o, tt, w), i(qn, E, o, p, R);
  }
  return {
    arrows: h,
    mount: I,
    destroy: b
  };
}
var br = Pe + "-interval";
function Or(t, n, e) {
  var r = H(t), a = r.on, s = r.bind, i = r.emit, v = ae(e.interval, t.go.bind(t, ">"), N), c = v.isPaused, f = n.Elements, u = n.Elements, _ = u.root, l = u.toggle, m = e.autoplay, E, o, g = m === "pause";
  function A() {
    m && (h(), l && x(l, Xt, f.track.id), g || I(), b());
  }
  function h() {
    e.pauseOnHover && s(_, "mouseenter mouseleave", function(P) {
      E = P.type === "mouseenter", F();
    }), e.pauseOnFocus && s(_, "focusin focusout", function(P) {
      o = P.type === "focusin", F();
    }), l && s(l, "click", function() {
      g ? I() : O(!0);
    }), a([gt, we, K], v.rewind), a(gt, D);
  }
  function I() {
    c() && n.Slides.isEnough() && (v.start(!e.resetProgress), o = E = g = !1, b(), i(_n));
  }
  function O(P) {
    P === void 0 && (P = !0), g = !!P, b(), c() || (v.pause(), i(An));
  }
  function F() {
    g || (E || o ? O(!1) : I());
  }
  function b() {
    l && (vt(l, yt, !g), x(l, tt, e.i18n[g ? "play" : "pause"]));
  }
  function N(P) {
    var M = f.bar;
    M && J(M, "width", P * 100 + "%"), i(Jn, P);
  }
  function D(P) {
    var M = n.Slides.getAt(P);
    v.set(M && +et(M.slide, br) || e.interval);
  }
  return {
    mount: A,
    destroy: v.cancel,
    play: I,
    pause: O,
    isPaused: c
  };
}
function Cr(t, n, e) {
  var r = H(t), a = r.on;
  function s() {
    e.cover && (a(yn, z(v, !0)), a([ht, j, K], z(i, !0)));
  }
  function i(c) {
    n.Slides.forEach(function(f) {
      var u = Yt(f.container || f.slide, "img");
      u && u.src && v(c, u, f);
    });
  }
  function v(c, f, u) {
    u.style("background", c ? 'center/cover no-repeat url("' + f.src + '")' : "", !0), ee(f, c ? "none" : "");
  }
  return {
    mount: s,
    destroy: z(i, !1)
  };
}
var Dr = 10, Pr = 600, pr = 0.6, wr = 1.5, Mr = 800;
function xr(t, n, e) {
  var r = H(t), a = r.on, s = r.emit, i = t.state.set, v = n.Move, c = v.getPosition, f = v.getLimit, u = v.exceededLimit, _ = v.translate, l, m, E = 1;
  function o() {
    a(gt, I), a([j, K], O);
  }
  function g(b, N, D, P, M) {
    var y = c();
    if (I(), D) {
      var T = n.Layout.sliderSize(), p = Se(b) * T * ne(q(b) / T) || 0;
      b = v.toPosition(n.Controller.toDest(b % T)) + p;
    }
    var R = fn(y, b, 1);
    E = 1, N = R ? 0 : N || kt(q(b - y) / wr, Mr), m = P, l = ae(N, A, z(h, y, b, M), 1), i(Wt), s(we), l.start();
  }
  function A() {
    i(Nt), m && m(), s(Pt);
  }
  function h(b, N, D, P) {
    var M = c(), y = b + (N - b) * F(P), T = (y - M) * E;
    _(M + T), t.is(Ot) && !D && u() && (E *= pr, q(T) < Dr && g(f(u(!0)), Pr, !1, m, !0));
  }
  function I() {
    l && l.cancel();
  }
  function O() {
    l && !l.isPaused() && (I(), A());
  }
  function F(b) {
    var N = e.easingFunc;
    return N ? N(b) : 1 - Math.pow(1 - b, 4);
  }
  return {
    mount: o,
    destroy: I,
    scroll: g,
    cancel: O
  };
}
var Tt = {
  passive: !1,
  capture: !0
};
function Vr(t, n, e) {
  var r = H(t), a = r.on, s = r.emit, i = r.bind, v = r.unbind, c = t.state, f = n.Move, u = n.Scroll, _ = n.Controller, l = n.Elements.track, m = n.Media.reduce, E = n.Direction, o = E.resolve, g = E.orient, A = f.getPosition, h = f.exceededLimit, I, O, F, b, N, D = !1, P, M, y;
  function T() {
    i(l, me, ye, Tt), i(l, _e, ye, Tt), i(l, pn, R, Tt), i(l, "click", X, {
      capture: !0
    }), i(l, "dragstart", ot), a([ht, j], p);
  }
  function p() {
    var L = e.drag;
    ze(!L), b = L === "free";
  }
  function R(L) {
    if (P = !1, !M) {
      var W = ge(L);
      Mn(L.target) && (W || !L.button) && (_.isBusy() ? ot(L, !0) : (y = W ? l : window, N = c.is([Ct, Wt]), F = null, i(y, me, U, Tt), i(y, _e, w, Tt), f.cancel(), u.cancel(), B(L)));
    }
  }
  function U(L) {
    if (c.is(qt) || (c.set(qt), s(Yn)), L.cancelable)
      if (N) {
        f.translate(I + wn(Y(L)));
        var W = k(L) > en, mt = D !== (D = h());
        (W || mt) && B(L), P = !0, s(Kn), ot(L);
      } else
        C(L) && (N = S(L), ot(L));
  }
  function w(L) {
    c.is(qt) && (c.set(Nt), s(Xn)), N && (G(L), ot(L)), v(y, me, U), v(y, _e, w), N = !1;
  }
  function X(L) {
    !M && P && ot(L, !0);
  }
  function B(L) {
    F = O, O = L, I = A();
  }
  function G(L) {
    var W = d(L), mt = V(W), wt = e.rewind && e.rewindByDrag;
    m(!1), b ? _.scroll(mt, 0, e.snap) : t.is(de) ? _.go(g(Se(W)) < 0 ? wt ? "<" : "-" : wt ? ">" : "+") : t.is(Ot) && D && wt ? _.go(h(!0) ? ">" : "<") : _.go(_.toDest(mt), !0), m(!0);
  }
  function S(L) {
    var W = e.dragMinThreshold, mt = Ft(W), wt = mt && W.mouse || 0, Vn = (mt ? W.touch : +W) || 10;
    return q(Y(L)) > (ge(L) ? Vn : wt);
  }
  function C(L) {
    return q(Y(L)) > q(Y(L, !0));
  }
  function d(L) {
    if (t.is(pt) || !D) {
      var W = k(L);
      if (W && W < en)
        return Y(L) / W;
    }
    return 0;
  }
  function V(L) {
    return A() + Se(L) * Rt(q(L) * (e.flickPower || 600), b ? 1 / 0 : n.Layout.listSize() * (e.flickMaxPages || 1));
  }
  function Y(L, W) {
    return ke(L, W) - ke($(L), W);
  }
  function k(L) {
    return Te(L) - Te($(L));
  }
  function $(L) {
    return O === L && F || O;
  }
  function ke(L, W) {
    return (ge(L) ? L.changedTouches[0] : L)["page" + o(W ? "Y" : "X")];
  }
  function wn(L) {
    return L / (D && t.is(Ot) ? Er : 1);
  }
  function Mn(L) {
    var W = e.noDrag;
    return !Gt(L, "." + On + ", ." + ve) && (!W || !Gt(L, W));
  }
  function ge(L) {
    return typeof TouchEvent < "u" && L instanceof TouchEvent;
  }
  function xn() {
    return N;
  }
  function ze(L) {
    M = L;
  }
  return {
    mount: T,
    disable: ze,
    isDragging: xn
  };
}
var Fr = {
  Spacebar: " ",
  Right: se,
  Left: ue,
  Up: Tn,
  Down: Sn
};
function Ge(t) {
  return t = dt(t) ? t : t.key, Fr[t] || t;
}
var nn = "keydown";
function Gr(t, n, e) {
  var r = H(t), a = r.on, s = r.bind, i = r.unbind, v = t.root, c = n.Direction.resolve, f, u;
  function _() {
    l(), a(j, m), a(j, l), a(gt, o);
  }
  function l() {
    var A = e.keyboard;
    A && (f = A === "global" ? window : v, s(f, nn, g));
  }
  function m() {
    i(f, nn);
  }
  function E(A) {
    u = A;
  }
  function o() {
    var A = u;
    u = !0, Ae(function() {
      u = A;
    });
  }
  function g(A) {
    if (!u) {
      var h = Ge(A);
      h === c(ue) ? t.go("<") : h === c(se) && t.go(">");
    }
  }
  return {
    mount: _,
    destroy: m,
    disable: E
  };
}
var Vt = Pe + "-lazy", Jt = Vt + "-srcset", Ur = "[" + Vt + "], [" + Jt + "]";
function kr(t, n, e) {
  var r = H(t), a = r.on, s = r.off, i = r.bind, v = r.emit, c = e.lazyLoad === "sequential", f = [ht, K, Kt, Pt], u = [];
  function _() {
    e.lazyLoad && (l(), a(K, l), c || a(f, m));
  }
  function l() {
    ct(u), n.Slides.forEach(function(A) {
      De(A.slide, Ur).forEach(function(h) {
        var I = et(h, Vt), O = et(h, Jt);
        if (I !== h.src || O !== h.srcset) {
          var F = e.classes.spinner, b = h.parentElement, N = Yt(b, "." + F) || It("span", F, b);
          u.push([h, A, N]), h.src || ee(h, "none");
        }
      });
    }), c && g();
  }
  function m() {
    u = u.filter(function(A) {
      var h = e.perPage * ((e.preloadPages || 1) + 1) - 1;
      return A[1].isWithin(t.index, h) ? E(A) : !0;
    }), u.length || s(f);
  }
  function E(A) {
    var h = A[0];
    it(A[1].slide, Ne), i(h, "load error", z(o, A)), x(h, "src", et(h, Vt)), x(h, "srcset", et(h, Jt)), at(h, Vt), at(h, Jt);
  }
  function o(A, h) {
    var I = A[0], O = A[1];
    ut(O.slide, Ne), h.type !== "error" && (At(A[2]), ee(I, ""), v(yn, I, O), v(bt)), c && g();
  }
  function g() {
    u.length && E(u.shift());
  }
  return {
    mount: _,
    destroy: z(ct, u)
  };
}
function zr(t, n, e) {
  var r = H(t), a = r.on, s = r.emit, i = r.bind, v = n.Slides, c = n.Elements, f = n.Controller, u = f.hasFocus, _ = f.getIndex, l = f.go, m = n.Direction.resolve, E = [], o, g;
  function A() {
    h(), a([j, K], A), e.pagination && v.isEnough() && (a([gt, we, Pt], D), I(), D(), s(jn, {
      list: o,
      items: E
    }, N(t.index)));
  }
  function h() {
    o && (At(c.pagination ? Et(o.children) : o), ut(o, g), ct(E), o = null), r.destroy();
  }
  function I() {
    var P = t.length, M = e.classes, y = e.i18n, T = e.perPage, p = u() ? P : zt(P / T);
    o = c.pagination || It("ul", M.pagination, c.track.parentElement), it(o, g = le + "--" + b()), x(o, st, "tablist"), x(o, tt, y.select), x(o, xe, b() === ce ? "vertical" : "");
    for (var R = 0; R < p; R++) {
      var U = It("li", null, o), w = It("button", {
        class: M.page,
        type: "button"
      }, U), X = v.getIn(R).map(function(G) {
        return G.slide.id;
      }), B = !u() && T > 1 ? y.pageX : y.slideX;
      i(w, "click", z(O, R)), e.paginationKeyboard && i(w, "keydown", z(F, R)), x(U, st, "presentation"), x(w, st, "tab"), x(w, Xt, X.join(" ")), x(w, tt, Ie(B, R + 1)), x(w, Lt, -1), E.push({
        li: U,
        button: w,
        page: R
      });
    }
  }
  function O(P) {
    l(">" + P, !0);
  }
  function F(P, M) {
    var y = E.length, T = Ge(M), p = b(), R = -1;
    T === m(se, !1, p) ? R = ++P % y : T === m(ue, !1, p) ? R = (--P + y) % y : T === "Home" ? R = 0 : T === "End" && (R = y - 1);
    var U = E[R];
    U && (un(U.button), l(">" + R), ot(M, !0));
  }
  function b() {
    return e.paginationDirection || e.direction;
  }
  function N(P) {
    return E[f.toPage(P)];
  }
  function D() {
    var P = N(_(!0)), M = N(_());
    if (P) {
      var y = P.button;
      ut(y, yt), at(y, je), x(y, Lt, -1);
    }
    if (M) {
      var T = M.button;
      it(T, yt), x(T, je, !0), x(T, Lt, "");
    }
    s(Zn, {
      list: o,
      items: E
    }, P, M);
  }
  return {
    items: E,
    mount: A,
    destroy: h,
    getAt: N,
    update: D
  };
}
var Br = [" ", "Enter"];
function Wr(t, n, e) {
  var r = e.isNavigation, a = e.slideFocus, s = [];
  function i() {
    t.options = {
      slideFocus: ie(a) ? r : a
    };
  }
  function v() {
    t.splides.forEach(function(o) {
      o.isParent || (u(t, o.splide), u(o.splide, t));
    }), r && _();
  }
  function c() {
    s.forEach(function(o) {
      o.destroy();
    }), ct(s);
  }
  function f() {
    c(), v();
  }
  function u(o, g) {
    var A = H(o);
    A.on(gt, function(h, I, O) {
      g.go(g.is(pt) ? O : h);
    }), s.push(A);
  }
  function _() {
    var o = H(t), g = o.on;
    g(dn, m), g(gn, E), g([ht, j], l), s.push(o), o.emit(mn, t.splides);
  }
  function l() {
    x(n.Elements.list, xe, e.direction === ce ? "vertical" : "");
  }
  function m(o) {
    t.go(o.index);
  }
  function E(o, g) {
    Oe(Br, Ge(g)) && (m(o), ot(g));
  }
  return {
    setup: i,
    mount: v,
    destroy: c,
    remount: f
  };
}
function Hr(t, n, e) {
  var r = H(t), a = r.bind, s = 0;
  function i() {
    e.wheel && a(n.Elements.track, "wheel", v, Tt);
  }
  function v(f) {
    if (f.cancelable) {
      var u = f.deltaY, _ = u < 0, l = Te(f), m = e.wheelMinThreshold || 0, E = e.wheelSleep || 0;
      q(u) > m && l - s > E && (t.go(_ ? "<" : ">"), s = l), c(_) && ot(f);
    }
  }
  function c(f) {
    return !e.releaseWheel || t.state.is(Ct) || n.Controller.getAdjacent(f) !== -1;
  }
  return {
    mount: i
  };
}
var Yr = 90;
function Kr(t, n, e) {
  var r = H(t), a = r.on, s = n.Elements.track, i = e.live && !e.isNavigation, v = It("span", fr), c = ae(Yr, z(u, !1));
  function f() {
    i && (l(!n.Autoplay.isPaused()), x(s, Qe, !0), v.textContent = "\u2026", a(_n, z(l, !0)), a(An, z(l, !1)), a([Kt, Pt], z(u, !0)));
  }
  function u(m) {
    x(s, Je, m), m ? (Ht(s, v), c.start()) : At(v);
  }
  function _() {
    at(s, [Ze, Qe, Je]), At(v);
  }
  function l(m) {
    i && x(s, Ze, m ? "off" : "polite");
  }
  return {
    mount: f,
    disable: l,
    destroy: _
  };
}
var Xr = /* @__PURE__ */ Object.freeze({
  __proto__: null,
  Media: er,
  Direction: nr,
  Elements: hr,
  Slides: _r,
  Layout: Ar,
  Clones: Tr,
  Move: Sr,
  Controller: Ir,
  Arrows: Rr,
  Autoplay: Or,
  Cover: Cr,
  Scroll: xr,
  Drag: Vr,
  Keyboard: Gr,
  LazyLoad: kr,
  Pagination: zr,
  Sync: Wr,
  Wheel: Hr,
  Live: Kr
}), $r = {
  prev: "Previous slide",
  next: "Next slide",
  first: "Go to first slide",
  last: "Go to last slide",
  slideX: "Go to slide %s",
  pageX: "Go to page %s",
  play: "Start autoplay",
  pause: "Pause autoplay",
  carousel: "carousel",
  slide: "slide",
  select: "Select a slide to show",
  slideLabel: "%s of %s"
}, qr = {
  type: "slide",
  role: "region",
  speed: 400,
  perPage: 1,
  cloneStatus: !0,
  arrows: !0,
  pagination: !0,
  paginationKeyboard: !0,
  interval: 5e3,
  pauseOnHover: !0,
  pauseOnFocus: !0,
  resetProgress: !0,
  easing: "cubic-bezier(0.25, 1, 0.5, 1)",
  drag: !0,
  direction: "ltr",
  trimSpace: !0,
  focusableNodes: "a, button, textarea, input, select, iframe",
  live: !0,
  classes: dr,
  i18n: $r,
  reducedMotion: {
    speed: 0,
    rewindSpeed: 0,
    autoplay: "pause"
  }
};
function jr(t, n, e) {
  var r = H(t), a = r.on;
  function s() {
    a([ht, K], function() {
      Ae(function() {
        n.Slides.style("transition", "opacity " + e.speed + "ms " + e.easing);
      });
    });
  }
  function i(v, c) {
    var f = n.Elements.track;
    J(f, "height", ft(Q(f).height)), Ae(function() {
      c(), J(f, "height", "");
    });
  }
  return {
    mount: s,
    start: i,
    cancel: ye
  };
}
function Zr(t, n, e) {
  var r = H(t), a = r.bind, s = n.Move, i = n.Controller, v = n.Scroll, c = n.Elements.list, f = z(J, c, "transition"), u;
  function _() {
    a(c, "transitionend", function(o) {
      o.target === c && u && (m(), u());
    });
  }
  function l(o, g) {
    var A = s.toPosition(o, !0), h = s.getPosition(), I = E(o);
    q(A - h) >= 1 && I >= 1 ? e.useScroll ? v.scroll(A, I, !1, g) : (f("transform " + I + "ms " + e.easing), s.translate(A, !0), u = g) : (s.jump(o), g());
  }
  function m() {
    f(""), v.cancel();
  }
  function E(o) {
    var g = e.rewindSpeed;
    if (t.is(Ot) && g) {
      var A = i.getIndex(!0), h = i.getEnd();
      if (A === 0 && o >= h || A >= h && o === 0)
        return g;
    }
    return e.speed;
  }
  return {
    mount: _,
    start: l,
    cancel: m
  };
}
var Jr = /* @__PURE__ */ function() {
  function t(e, r) {
    this.event = H(), this.Components = {}, this.state = Qn(St), this.splides = [], this._o = {}, this._E = {};
    var a = dt(e) ? cn(document, e) : e;
    xt(a, a + " is invalid."), this.root = a, r = lt({
      label: et(a, tt) || "",
      labelledby: et(a, Me) || ""
    }, qr, t.defaults, r || {});
    try {
      lt(r, JSON.parse(et(a, Pe)));
    } catch {
      xt(!1, "Invalid JSON");
    }
    this._o = Object.create(lt({}, r));
  }
  var n = t.prototype;
  return n.mount = function(r, a) {
    var s = this, i = this.state, v = this.Components;
    xt(i.is([St, Qt]), "Already mounted!"), i.set(St), this._C = v, this._T = a || this._T || (this.is(de) ? jr : Zr), this._E = r || this._E;
    var c = Ut({}, Xr, this._E, {
      Transition: this._T
    });
    return _t(c, function(f, u) {
      var _ = f(s, v, s._o);
      v[u] = _, _.setup && _.setup();
    }), _t(v, function(f) {
      f.mount && f.mount();
    }), this.emit(ht), it(this.root, vr), i.set(Nt), this.emit($e), this;
  }, n.sync = function(r) {
    return this.splides.push({
      splide: r
    }), r.splides.push({
      splide: this,
      isParent: !0
    }), this.state.is(Nt) && (this._C.Sync.remount(), r.Components.Sync.remount()), this;
  }, n.go = function(r) {
    return this._C.Controller.go(r), this;
  }, n.on = function(r, a) {
    return this.event.on(r, a), this;
  }, n.off = function(r) {
    return this.event.off(r), this;
  }, n.emit = function(r) {
    var a;
    return (a = this.event).emit.apply(a, [r].concat(Et(arguments, 1))), this;
  }, n.add = function(r, a) {
    return this._C.Slides.add(r, a), this;
  }, n.remove = function(r) {
    return this._C.Slides.remove(r), this;
  }, n.is = function(r) {
    return this._o.type === r;
  }, n.refresh = function() {
    return this.emit(K), this;
  }, n.destroy = function(r) {
    r === void 0 && (r = !0);
    var a = this.event, s = this.state;
    return s.is(St) ? H(this).on($e, this.destroy.bind(this, r)) : (_t(this._C, function(i) {
      i.destroy && i.destroy(r);
    }, !0), a.emit(hn), a.destroy(), r && ct(this.splides), s.set(Qt)), this;
  }, Fn(t, [{
    key: "options",
    get: function() {
      return this._o;
    },
    set: function(r) {
      this._C.Media.set(r, !0);
    }
  }, {
    key: "length",
    get: function() {
      return this._C.Slides.getLength(!0);
    }
  }, {
    key: "index",
    get: function() {
      return this._C.Controller.getIndex();
    }
  }]), t;
}(), Ue = Jr;
Ue.defaults = {};
Ue.STATES = Un;
document.addEventListener("DOMContentLoaded", () => {
  new Ue("#psacc_slider", {
    type: "loop",
    autoplay: !0
  }).mount();
});
