/* SMLIFE Core - Bang quan tri nhom Mua kem (1.23.0)
 * Du lieu nap san trong window.SMLIFE_MK. Sap xep, loc, tim kiem chay tren
 * trinh duyet; luu / bat tat / xoa / doi uu tien goi AJAX.
 */
(function () {
	'use strict';
	var D = window.SMLIFE_MK;
	if (!D) { return; }
	var tb = document.querySelector('#mk-tb tbody');
	if (!tb) { return; }

	var DS = D.nhom.slice();          /* thu tu uu tien = thu tu mang */
	var DM = D.dm || {};
	var CP = D.cp || {};
	var TT_MA = { xoa: ['bad', 'Coupon đã bị xoá'], het_han: ['bad', 'Coupon hết hạn'], het_luot: ['warn', 'Coupon hết lượt dùng'], nhap: ['warn', 'Coupon chưa đăng'], chu: ['warn', 'Chưa gắn coupon'] };
	function ma_loi(g) { return g.ma_tt && TT_MA[g.ma_tt] ? TT_MA[g.ma_tt] : null; }
	var SX = { k: 'ut', d: 1 };       /* cot dang sap xep, chieu */
	var DANG_SUA = null;              /* gid dang mo sua nhanh (0 = nhom moi) */

	/* ---------- tien ich ---------- */
	function $(s, r) { return (r || document).querySelector(s); }
	function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
	function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
	function bo_dau(s) { return String(s || '').normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/đ/g, 'd').replace(/Đ/g, 'D').toLowerCase(); }
	function ten_dm(id) { return DM[id] ? DM[id].n : (id ? '#' + id : ''); }
	function path_dm(id) { return DM[id] ? DM[id].p : ''; }
	function so_sp(g) { return g.sp ? g.sp.split(',').filter(function (x) { return parseInt(x, 10) > 0; }).length : 0; }
	function uu_tien(gid) { for (var i = 0; i < DS.length; i++) { if (DS[i].gid === gid) { return i + 1; } } return 0; }

	function goi(viec, du_lieu) {
		var f = new FormData();
		f.append('action', viec);
		f.append('_n', D.n);
		Object.keys(du_lieu || {}).forEach(function (k) { f.append(k, du_lieu[k]); });
		return fetch(D.ajax, { method: 'POST', credentials: 'same-origin', body: f })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (!j || !j.success) { throw new Error((j && j.data && j.data.m) || 'Lỗi, chưa lưu được.'); }
				return j.data || {};
			});
	}

	function bao(msg, loi) {
		var b = $('#mk-bao');
		if (!b) {
			b = document.createElement('div');
			b.id = 'mk-bao';
			document.body.appendChild(b);
		}
		b.className = 'mk-bao' + (loi ? ' loi' : '');
		b.textContent = msg;
		b.style.opacity = '1';
		clearTimeout(bao.t);
		bao.t = setTimeout(function () { b.style.opacity = '0'; }, loi ? 4200 : 2200);
	}

	/* Danh muc trung: cung mot danh muc nam o 2 nhom dang bat -> nhom sau bi che */
	function tinh_trung() {
		var chiem = {}, out = {};
		DS.forEach(function (g) {
			if (!g.on) { return; }
			g.terms.forEach(function (t) {
				if (chiem[t] && chiem[t] !== g.gid) {
					(out[g.gid] = out[g.gid] || []).push({ t: t, boi: chiem[t] });
				} else if (!chiem[t]) {
					chiem[t] = g.gid;
				}
			});
		});
		return out;
	}

	/* ---------- loc + sap xep ---------- */
	function khoa_sx(g, k) {
		switch (k) {
			case 'ut': return uu_tien(g.gid);
			case 'on': return g.on ? 0 : 1;
			case 'td': return bo_dau(g.tieu_de || '~');
			case 'ap': return bo_dau(g.terms.map(ten_dm).sort().join(', ') || '~');
			case 'dm': return g.dm ? bo_dau(path_dm(g.dm) || ten_dm(g.dm)) : '~~~';
			case 'ma': return bo_dau(g.ma || '~');
			case 'sp': return so_sp(g);
		}
		return 0;
	}

	function danh_sach() {
		var tim = bo_dau($('#mk-tim').value.trim());
		var ldm = $('#mk-loc-dm').value;
		var ltt = $('#mk-loc-tt').value;
		var trung = tinh_trung();
		var ds = DS.filter(function (g) {
			if (ldm !== '' && String(g.dm || 0) !== ldm) { return false; }
			if (ltt === '1' && !g.on) { return false; }
			if (ltt === '0' && g.on) { return false; }
			if (ltt === 'trung' && !trung[g.gid]) { return false; }
			if (ltt === 'ma' && !ma_loi(g)) { return false; }
			if (tim) {
				var chu = bo_dau([g.tieu_de, g.ghi_chu, g.ma, g.sp, ten_dm(g.dm), path_dm(g.dm)].concat(g.terms.map(path_dm)).join(' | '));
				if (chu.indexOf(tim) === -1) { return false; }
			}
			return true;
		});
		ds.sort(function (a, b) {
			var x = khoa_sx(a, SX.k), y = khoa_sx(b, SX.k);
			if (x < y) { return -SX.d; }
			if (x > y) { return SX.d; }
			return uu_tien(a.gid) - uu_tien(b.gid);
		});
		return { ds: ds, trung: trung };
	}

	/* ---------- ve bang ---------- */
	function chip_dm(ids, max) {
		var h = '', i;
		for (i = 0; i < ids.length && i < max; i++) {
			h += '<span class="mk-chip" title="' + esc(path_dm(ids[i])) + '">' + esc(ten_dm(ids[i])) + '</span>';
		}
		if (ids.length > max) {
			h += '<span class="mk-chip mk-chip-them" title="' + esc(ids.slice(max).map(ten_dm).join(', ')) + '">+' + (ids.length - max) + '</span>';
		}
		return h;
	}

	function dong(g, trung) {
		var n = so_sp(g), ut = uu_tien(g.gid), tr = trung[g.gid];
		var h = '<tr class="mk-dong' + (g.on ? '' : ' is-tat') + '" data-gid="' + g.gid + '">';
		h += '<td class="c-ut"><span class="mk-ut">' + ut + '</span><span class="mk-doi"><button type="button" class="mk-len" title="Ưu tiên cao hơn"' + (ut <= 1 ? ' disabled' : '') + '>▲</button><button type="button" class="mk-xuong" title="Ưu tiên thấp hơn"' + (ut >= DS.length ? ' disabled' : '') + '>▼</button></span></td>';
		h += '<td class="c-on"><label class="smc-sw"><input type="checkbox" class="mk-bat"' + (g.on ? ' checked' : '') + ' /><i></i></label></td>';
		var o_td = '<td class="c-td"><b class="mk-td">' + (g.tieu_de ? esc(g.tieu_de) : '<i>Chưa đặt tiêu đề</i>') + '</b>' + (g.ghi_chu ? '<span class="mk-dg">' + esc(g.ghi_chu) + '</span>' : '') + '</td>';
		h += '<td class="c-ap">' + chip_dm(g.terms, 3);
		if (tr) {
			h += '<span class="smc-pill warn mk-trung" title="' + esc(tr.map(function (x) { return ten_dm(x.t) + ' đã thuộc nhóm ưu tiên ' + uu_tien(x.boi); }).join('\n')) + '">Trùng ' + tr.length + ' danh mục, bị nhóm ' + tr.map(function (x) { return uu_tien(x.boi); }).filter(function (v, i, a) { return a.indexOf(v) === i; }).join(', ') + ' che</span>';
		}
		h += '</td>';
		h += '<td class="c-dm">' + (g.dm ? '<b>' + esc(ten_dm(g.dm)) + '</b>' + (path_dm(g.dm) !== ten_dm(g.dm) ? '<span class="mk-path">' + esc(path_dm(g.dm)) + '</span>' : '') : '<span class="smc-pill mute">Chưa đặt</span>') + '</td>';
		var ml = ma_loi(g);
		h += '<td class="c-ma">' + (g.ma ? (g.ma_id ? '<a class="mk-ma" href="' + D.sua + g.ma_id + '" target="_blank" rel="noopener" title="Mở cài đặt coupon ' + esc(g.ma.toUpperCase()) + ' (tab mới)">' + esc(g.ma.toUpperCase()) + ' <span aria-hidden="true">↗</span></a>' : '<span class="mk-ma">' + esc(g.ma.toUpperCase()) + '</span>') + (g.rang ? '<span class="mk-rang" title="Mã chỉ dùng được khi giỏ có sản phẩm thuộc danh mục áp dụng">ràng buộc</span>' : '') : (ml ? '' : '<span class="mk-nhat">-</span>'));
		if (ml) { h += '<span class="smc-pill ' + ml[0] + ' mk-ma-loi">' + ml[1] + '</span>'; }
		h += '</td>';
		h += '<td class="c-sp"><span class="smc-pill ' + (n === 0 ? 'bad' : (n < 10 ? 'warn' : 'ok')) + '" title="' + esc(g.sp) + '">' + n + '/10</span></td>';
		h += o_td;
		h += '<td class="c-tt"><button type="button" class="button mk-sua-nut">Sửa nhanh</button><button type="button" class="mk-xoa" title="Xoá nhóm">×</button></td>';
		h += '</tr>';
		return h;
	}

	function ve() {
		var kq = danh_sach(), h = '', cum = null;
		if (DANG_SUA === 0) { h += khung_sua(null); }
		kq.ds.forEach(function (g) {
			if (SX.k === 'dm') {
				var c = g.dm || 0;
				if (c !== cum) {
					cum = c;
					var dem = kq.ds.filter(function (x) { return (x.dm || 0) === c; }).length;
					h += '<tr class="mk-cum"><td colspan="8">' + (c ? '<b>' + esc(ten_dm(c)) + '</b>' + (path_dm(c) !== ten_dm(c) ? ' <span>' + esc(path_dm(c)) + '</span>' : '') : '<b>Chưa đặt danh mục bán kèm</b>') + '<em>' + dem + ' nhóm</em></td></tr>';
				}
			}
			h += dong(g, kq.trung);
			if (DANG_SUA === g.gid) { h += khung_sua(g); }
		});
		tb.innerHTML = h;
		$('#mk-trong').hidden = kq.ds.length > 0 || DANG_SUA === 0;
		var bat = DS.filter(function (g) { return g.on; }).length;
		var so_trung = Object.keys(kq.trung).length;
		var so_ma = DS.filter(ma_loi).length;
		$('#mk-dem').innerHTML = '<b>' + kq.ds.length + '</b>/' + DS.length + ' nhóm · ' + bat + ' đang bật' + (so_trung ? ' · <span class="mk-dem-trung">' + so_trung + ' nhóm trùng danh mục</span>' : '') + (so_ma ? ' · <span class="mk-dem-trung">' + so_ma + ' nhóm có mã lỗi</span>' : '');
		$$('#mk-tb thead th[data-s]').forEach(function (th) {
			th.classList.toggle('is-sx', th.getAttribute('data-s') === SX.k);
			th.setAttribute('data-d', SX.d > 0 ? 'tang' : 'giam');
		});
		if (DANG_SUA !== null) { mo_khung(); }
	}

	function nap_loc_dm() {
		var sel = $('#mk-loc-dm'), cu = sel.value, dem = {};
		DS.forEach(function (g) { var k = g.dm || 0; dem[k] = (dem[k] || 0) + 1; });
		var ds = Object.keys(dem).filter(function (k) { return k !== '0'; }).sort(function (a, b) { return bo_dau(path_dm(a)) < bo_dau(path_dm(b)) ? -1 : 1; });
		var h = '<option value="">Mọi danh mục bán kèm</option>';
		if (dem[0]) { h += '<option value="0">Chưa đặt (' + dem[0] + ')</option>'; }
		ds.forEach(function (k) { h += '<option value="' + k + '">' + esc(ten_dm(k)) + ' (' + dem[k] + ')</option>'; });
		sel.innerHTML = h;
		sel.value = cu;
		if (sel.value !== cu) { sel.value = ''; }
	}

	/* ---------- sua nhanh ---------- */
	function khung_sua(g) {
		var moi = !g;
		g = g || { gid: 0, on: 1, terms: [], tieu_de: '', ma: '', ma_id: 0, ghi_chu: '', sp: '', rang: 1, dm: 0 };
		var ma_opts = '<option value="0">Không dùng mã giảm giá</option>', co = false;
		/* Nhom cu luu chu ma ma khong tim thay coupon: mac dinh giu nguyen chu cu, tranh lo tay xoa ma */
		if (g.ma_tt === 'chu') { ma_opts += '<option value="-1" selected>Giữ mã chữ cũ ' + esc(g.ma) + ' (chưa liên kết coupon)</option>'; }
		Object.keys(CP).forEach(function (id) {
			var c = CP[id], tt = TT_MA[c.tt] ? ' · ' + TT_MA[c.tt][1].replace('Coupon ', '') : '';
			if (parseInt(id, 10) === g.ma_id) { co = true; }
			ma_opts += '<option value="' + id + '"' + (parseInt(id, 10) === g.ma_id ? ' selected' : '') + '>' + esc(c.m + ' · giảm ' + c.g + (c.h ? ' · HSD ' + c.h : '') + tt) + '</option>';
		});
		if (g.ma_id && !co) { ma_opts += '<option value="' + g.ma_id + '" selected>' + esc((g.ma || '#' + g.ma_id) + ' · coupon không còn') + '</option>'; }
		var opts = '<option value="0">Không hiện thẻ Xem tất cả</option>';
		Object.keys(DM).forEach(function (id) {
			opts += '<option value="' + id + '"' + (parseInt(id, 10) === g.dm ? ' selected' : '') + '>' + esc(DM[id].p) + ' (' + DM[id].c + ')</option>';
		});
		var ds = '';
		Object.keys(DM).forEach(function (id) {
			var c = g.terms.indexOf(parseInt(id, 10)) !== -1;
			ds += '<label data-k="' + esc(bo_dau(DM[id].p)) + '"' + (c ? ' class="is-chon"' : '') + '><input type="checkbox" value="' + id + '"' + (c ? ' checked' : '') + ' /> ' + esc(DM[id].p) + ' <small>' + DM[id].c + '</small></label>';
		});
		var h = '<tr class="mk-sua' + (moi ? ' is-moi' : '') + '" data-gid="' + g.gid + '"><td colspan="8"><div class="mk-sua-in">';
		h += '<div class="mk-sua-hd"><b>' + (moi ? 'Thêm nhóm mua kèm' : 'Sửa nhóm ưu tiên ' + uu_tien(g.gid)) + '</b><span>Ctrl + Enter để lưu, Esc để đóng</span></div>';
		h += '<div class="mk-sua-grid">';
		/* Cot 1: noi dung */
		h += '<div class="mk-cot"><label class="mk-f"><span>Tiêu đề</span><input type="text" name="tieu_de" value="' + esc(g.tieu_de) + '" placeholder="Mua kèm nệm giảm 5%" /></label>';
		h += '<label class="mk-f"><span>Diễn giải chương trình</span><textarea name="ghi_chu" rows="4" placeholder="Áp dụng khi mua cùng đơn với sản phẩm đang xem. Nhập mã ở bước thanh toán.">' + esc(g.ghi_chu) + '</textarea><em>Chữ xám dưới tiêu đề, hiện khi khách mở thẻ.</em></label>';
		h += '<div class="mk-f-hang"><label class="mk-f"><span>Mã giảm giá <em>liên kết coupon, bên Coupon đổi mã thì tự đổi theo</em></span><span class="mk-ma-chon"><select name="ma_id">' + ma_opts + '</select><a class="button mk-mo-cp" target="_blank" rel="noopener" href="' + (g.ma_id ? D.sua + g.ma_id : '#') + '"' + (g.ma_id ? '' : ' hidden') + ' title="Mở cài đặt coupon (tab mới)">Mở coupon ↗</a></span>' + (g.ma_tt === 'chu' ? '<em class="mk-ma-cu">Nhóm đang lưu chữ "' + esc(g.ma) + '" nhưng không tìm thấy coupon này. Chọn lại coupon.</em>' : '') + '</label>';
		h += '<label class="mk-tich"><input type="checkbox" name="on"' + (g.on ? ' checked' : '') + ' /> Bật nhóm</label></div>';
		h += '<label class="mk-tich"><input type="checkbox" name="rang"' + (g.rang ? ' checked' : '') + ' /> Ràng buộc mã: chỉ dùng được khi giỏ có sản phẩm thuộc danh mục áp dụng</label></div>';
		/* Cot 2: danh muc ap dung */
		h += '<div class="mk-cot"><div class="mk-f"><span>Danh mục áp dụng <em class="mk-so-chon"></em></span><input type="search" class="mk-loc-ap" placeholder="Lọc danh mục..." />';
		h += '<div class="mk-ds-ap">' + ds + '</div><label class="mk-tich mk-chi-chon"><input type="checkbox" /> Chỉ hiện danh mục đã chọn</label></div></div>';
		/* Cot 3: ban kem */
		h += '<div class="mk-cot"><label class="mk-f"><span>Danh mục bán kèm</span><select name="dm">' + opts + '</select><em>Nguồn sản phẩm kèm và đích của thẻ "Xem tất cả".</em></label>';
		h += '<div class="mk-f"><span>Sản phẩm kèm <em>ID, cách nhau dấu phẩy, tối đa 10, thứ tự nhập là thứ tự hiện</em></span>';
		h += '<div class="mk-sp-hang"><input type="text" name="sp" value="' + esc(g.sp) + '" placeholder="1234, 5678" /><button type="button" class="button mk-lay">Lấy 10 SP đầu danh mục bán kèm</button></div>';
		h += '<div class="mk-sp-xem"></div></div></div>';
		h += '</div>';
		h += '<div class="mk-sua-ft"><span class="mk-sua-loi"></span><button type="button" class="button mk-huy">Huỷ</button><button type="button" class="button button-primary mk-luu">' + (moi ? 'Thêm nhóm' : 'Lưu nhóm') + '</button></div>';
		h += '</div></td></tr>';
		return h;
	}

	function mo_khung() {
		var k = $('tr.mk-sua', tb);
		if (!k) { return; }
		dem_chon(k);
		xem_sp(k, false);
		if (!mo_khung.da) { var i = $('input[name="tieu_de"]', k); if (i) { i.focus(); } }
		mo_khung.da = true;
	}

	function dem_chon(k) {
		var n = $$('.mk-ds-ap input:checked', k).length;
		$('.mk-so-chon', k).textContent = n ? n + ' đã chọn' : 'chưa chọn';
		$$('.mk-ds-ap label', k).forEach(function (l) { l.classList.toggle('is-chon', $('input', l).checked); });
	}

	function loc_ap(k) {
		var q = bo_dau($('.mk-loc-ap', k).value.trim()), chi = $('.mk-chi-chon input', k).checked;
		$$('.mk-ds-ap label', k).forEach(function (l) {
			var hien = (!q || l.getAttribute('data-k').indexOf(q) !== -1) && (!chi || $('input', l).checked);
			l.style.display = hien ? '' : 'none';
		});
	}

	function xem_sp(k, tu_dm) {
		var o = $('.mk-sp-xem', k), inp = $('input[name="sp"]', k), dm = $('select[name="dm"]', k).value;
		if (tu_dm && dm === '0') { o.innerHTML = '<p class="mk-sua-loi">Chọn danh mục bán kèm trước.</p>'; return; }
		var ids = inp.value.trim();
		if (!tu_dm && !ids) { o.innerHTML = ''; return; }
		o.innerHTML = '<p class="mk-nhat">Đang tải...</p>';
		goi('smlife_mk_lay', tu_dm ? { dm: dm } : { ids: ids }).then(function (d) {
			if (tu_dm) { inp.value = d.ids.join(', '); }
			if (!d.sp.length) { o.innerHTML = '<p class="mk-nhat">' + (tu_dm ? 'Danh mục này chưa có sản phẩm đang bán.' : '') + '</p>'; return; }
			var h = '<ol class="mk-sp-ds">';
			d.sp.forEach(function (p) {
				h += '<li' + (p.loi ? ' class="is-loi"' : '') + '>' + (p.img ? '<img src="' + esc(p.img) + '" alt="" />' : '<span class="mk-img-trong"></span>') + '<span class="mk-sp-ten"><b>' + esc(p.n || ('#' + p.id)) + '</b><small>#' + p.id + (p.gia ? ' · ' + esc(p.gia) : '') + (p.loi ? ' · <i>' + esc(p.loi) + '</i>' : '') + '</small></span></li>';
			});
			h += '</ol>';
			if (tu_dm) { h = '<p class="mk-nhat">Đã điền 10 sản phẩm đầu theo thứ tự đang hiện trên trang danh mục. Sửa ô ID nếu cần.</p>' + h; }
			o.innerHTML = h;
		}).catch(function (e) { o.innerHTML = '<p class="mk-sua-loi">' + esc(e.message) + '</p>'; });
	}

	function doc_khung(k) {
		return {
			on: $('input[name="on"]', k).checked ? 1 : 0,
			tieu_de: $('input[name="tieu_de"]', k).value.trim(),
			ghi_chu: $('textarea[name="ghi_chu"]', k).value.trim(),
			ma_id: parseInt($('select[name="ma_id"]', k).value, 10) || 0,
			ma_cu: $('select[name="ma_id"]', k).value === '-1' ? 1 : 0,
			rang: $('input[name="rang"]', k).checked ? 1 : 0,
			dm: parseInt($('select[name="dm"]', k).value, 10) || 0,
			sp: $('input[name="sp"]', k).value,
			terms: $$('.mk-ds-ap input:checked', k).map(function (c) { return parseInt(c.value, 10); })
		};
	}

	function luu(k) {
		var gid = parseInt(k.getAttribute('data-gid'), 10) || 0, g = doc_khung(k), nut = $('.mk-luu', k);
		if (!g.terms.length) { $('.mk-sua-loi', k).textContent = 'Chọn ít nhất 1 danh mục áp dụng.'; return; }
		nut.disabled = true;
		goi('smlife_mk_luu', { gid: gid, g: JSON.stringify(g) }).then(function (d) {
			var moi = d.g, cu = false;
			DS = DS.map(function (x) { if (x.gid === moi.gid) { cu = true; return moi; } return x; });
			if (!cu) { DS.push(moi); }
			DANG_SUA = null;
			mo_khung.da = false;
			nap_loc_dm();
			ve();
			var r = $('tr.mk-dong[data-gid="' + moi.gid + '"]', tb);
			if (r) { r.classList.add('is-vua'); r.scrollIntoView({ block: 'nearest' }); }
			bao(cu ? 'Đã lưu nhóm.' : 'Đã thêm nhóm.');
		}).catch(function (e) { nut.disabled = false; $('.mk-sua-loi', k).textContent = e.message; });
	}

	/* ---------- su kien ---------- */
	$('#mk-tb thead').addEventListener('click', function (e) {
		var th = e.target.closest('th[data-s]');
		if (!th) { return; }
		var k = th.getAttribute('data-s');
		SX = { k: k, d: SX.k === k ? -SX.d : 1 };
		ve();
	});
	['#mk-tim', '#mk-loc-dm', '#mk-loc-tt'].forEach(function (s) {
		$(s).addEventListener(s === '#mk-tim' ? 'input' : 'change', ve);
	});
	$('#mk-them').addEventListener('click', function () {
		DANG_SUA = 0;
		mo_khung.da = false;
		ve();
	});
	$('#mk-module').addEventListener('change', function () {
		var c = this;
		goi('smlife_mk_module', { on: c.checked ? 1 : 0 })
			.then(function () { bao(c.checked ? 'Đã bật khối mua kèm trên website.' : 'Đã ẩn khối mua kèm trên toàn website.'); })
			.catch(function (e) { c.checked = !c.checked; bao(e.message, true); });
	});

	tb.addEventListener('click', function (e) {
		var t = e.target, r = t.closest('tr'), gid = r ? parseInt(r.getAttribute('data-gid'), 10) : 0;
		if (t.closest('.mk-sua-nut')) {
			DANG_SUA = DANG_SUA === gid ? null : gid;
			mo_khung.da = false;
			ve();
		} else if (t.closest('.mk-huy')) {
			DANG_SUA = null;
			ve();
		} else if (t.closest('.mk-luu')) {
			luu(r);
		} else if (t.closest('.mk-lay')) {
			xem_sp(r, true);
		} else if (t.closest('.mk-xoa')) {
			var g = DS.filter(function (x) { return x.gid === gid; })[0];
			if (!g || !window.confirm('Xoá nhóm "' + (g.tieu_de || 'chưa đặt tiêu đề') + '"?')) { return; }
			goi('smlife_mk_xoa', { gid: gid }).then(function () {
				DS = DS.filter(function (x) { return x.gid !== gid; });
				if (DANG_SUA === gid) { DANG_SUA = null; }
				nap_loc_dm();
				ve();
				bao('Đã xoá nhóm.');
			}).catch(function (er) { bao(er.message, true); });
		} else if (t.closest('.mk-len') || t.closest('.mk-xuong')) {
			goi('smlife_mk_doi', { gid: gid, h: t.closest('.mk-len') ? 'len' : 'xuong' }).then(function (d) {
				var map = {};
				DS.forEach(function (x) { map[x.gid] = x; });
				DS = d.thu_tu.map(function (id) { return map[id]; }).filter(Boolean);
				ve();
			}).catch(function (er) { bao(er.message, true); });
		}
	});

	tb.addEventListener('change', function (e) {
		var t = e.target, r = t.closest('tr');
		if (t.classList.contains('mk-bat')) {
			var gid = parseInt(r.getAttribute('data-gid'), 10), on = t.checked ? 1 : 0;
			goi('smlife_mk_bat', { gid: gid, on: on }).then(function () {
				DS.forEach(function (x) { if (x.gid === gid) { x.on = on; } });
				ve();
				bao(on ? 'Đã bật nhóm.' : 'Đã tắt nhóm.');
			}).catch(function (er) { t.checked = !t.checked; bao(er.message, true); });
		} else if (t.closest('.mk-ds-ap')) {
			dem_chon(r);
		} else if (t.closest('.mk-chi-chon')) {
			loc_ap(r);
		} else if (t.name === 'ma_id') {
			var a = $('.mk-mo-cp', r), v = parseInt(t.value, 10) || 0;
			a.hidden = v <= 0;
			if (v > 0) { a.href = D.sua + v; }
		} else if (t.name === 'sp') {
			xem_sp(r, false);
		}
	});

	tb.addEventListener('input', function (e) {
		if (e.target.classList.contains('mk-loc-ap')) { loc_ap(e.target.closest('tr')); }
	});

	tb.addEventListener('keydown', function (e) {
		var k = e.target.closest('tr.mk-sua');
		if (!k) { return; }
		if (e.key === 'Escape') { DANG_SUA = null; ve(); }
		if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); luu(k); }
		if (e.key === 'Enter' && e.target.classList.contains('mk-loc-ap')) { e.preventDefault(); }
	});

	nap_loc_dm();
	ve();
})();
