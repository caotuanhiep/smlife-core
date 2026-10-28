=== SMLIFE Core ===
Contributors: Cao Tuan Hiep
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.3.5

Bo cong cu van hanh website cho he sinh thai SMLIFE.

== Cai dat ==

1. Vao Plugins > Add New > Upload Plugin, chon file smlife-core.zip, bam Install Now.
2. TRUOC KHI KICH HOAT: vao Code Snippets, tat toan bo snippet co ten bat dau bang
   "SMLIFE CORE". Neu khong tat, plugin se tu bo qua module trung va bao o Bang dieu khien.
3. Kich hoat plugin.
4. Vao menu SMLIFE Core > Bang dieu khien de bat tat tung tinh nang.

== Du lieu ==

Plugin dung lai nguyen ven cac option ma snippet cu da luu, nen moi cau hinh
da thiet lap truoc do deu giu nguyen sau khi chuyen sang plugin.

Go plugin KHONG xoa du lieu. Cai lai la dung tiep.

== Danh sach module ==

Ban hang va Thanh toan
  - Gop Cart + Checkout
  - Giao dien Checkout
  - Nut Mua ngay
  - Form Mua ngay
  - Cau truc gia

Lien he va Ho tro
  - Hotline Header
  - Menu Mobile
  - Desktop Chat Button
  - Lich Truc Sales
  - Promo Bar

He thong
  - Email Setting
  - SMTP
  - Tinh chinh he thong

== Lich su ==

= 1.3.5 =
* Noi dung Danh muc: DOI CACH HIEU KE THUA. Truoc day danh muc con bo trong
  slot nao thi tu lay cua danh muc cha. Nay MAC DINH KHONG lan len cha nua:
  moi danh muc chi dung dung noi dung cua chinh no, con bo trong thi slot do
  khong hien du cha co dat.
* Noi dung Danh muc: them o "Ke thua tu danh muc cha" trong trang quan tri de
  bat lai cach cu khi can. Bat len thi o chon trong bang moi ghi "theo cha".
* Noi dung Danh muc: ghi chu duoi bang tu doi theo che do dang bat.

= 1.3.4 =
* The san pham: khe giua cac the mac dinh 10px len 15px, 10px nhin chat qua.
* The san pham: mau vien tra ve #ececec nhu cu.
  Hai thu nay van chinh duoc trong trang quan tri.

= 1.3.3 =
* The san pham: khoang ho duoi tag len 20px, va chinh duoc trong trang
  quan tri (4 den 40px).
* The san pham: vien nhat lai, mac dinh tu #ececec sang #f0f0f0.
  Ghi chu: be day duoi 1px man hinh thuong khong ve duoc, nen muon vien
  nhin mong hon thi lam nhat mau chu khong giam be day.
* The san pham: them o chinh KHE GIUA CAC THE. Mac dinh 10px, giao dien goc
  cua Flatsome la 20px. Khe hep lai thi anh san pham to ra - do thuc te o
  man 909px, 4 cot: the 164px -> 171px, khe 20px -> 10px.
  Chinh ca le am cua hang lan dem cua cot cung luc nen mep hang khong lech.
* The san pham: bo khoi canh bao "Truoc khi bat" trong trang quan tri, da
  don xong hai snippet nen khong can nhac nua.

= 1.3.2 =
* The san pham: them khoang tho duoi hai tag cho the do chat (day the tu
  10px len 15px).
* The san pham: badge goc trai lam lai cho vuong vuc va nho gon - chu 14px
  xuong 11px, bot dem hai ben, bo goc nhe ben phai thay vi goc duoi.
* The san pham: badge tut xuong de ne goc bo cong cua vien. Khoang tut lay
  theo DUNG do bo goc dang dat (bo goc 12px thi tut 14px, 20px thi tut 22px),
  nen doi do bo goc la badge tu ne theo, khong phai chinh tay.
  Tat vien di thi badge tro ve dung vi tri nguyen ban nhu cu.

= 1.3.1 =
* The san pham: vá lỗi tag tran ra ngoai vien o the qua hep.
  Do thuc te: the 300px thi chu tag 12px, 220px -> 9.2px, 170px -> 8.5px la
  cham san. Duoi nguong do ep tiep thi tag tran ra ngoai vien the. Nay tu
  168px tro xuong hai tag tu xuong dong, tran van xau hon xuong dong.
  Anh huong dien thoai man hinh nho (360px tro xuong), noi the chi con ~160px.
* Da go phan trung o hai snippet (lam truc tiep tren web, khong phai viec
  cua ban cai dat nay):
  - Snippet 11 - xoa khoi MODULE 1, con lai MODULE 2 va 3 nguyen ven.
  - Snippet 20 - xoa 4 khoi: badge, nut phu 2.2, gradient 3 nut, va khoi
    @media ep 9px !important. Da dat ghi chu chi duong sang plugin.

= 1.3.0 =
* The san pham: gom NGUYEN CUM ve mot cho. Truoc day cum nay nam rai o hai
  snippet, hai ben cung chinh mot thu nen danh nhau:
  - Snippet 11 (PHP) - khoi MODULE 1 tao 3 tag va badge tu truong ACF.
  - Snippet 20 (CSS) - mau 3 tag, badge vang, va mot khoi @media ep co chu
    tag ve 9px !important.
  Nay module tu lam ca hai phan, co cong tac bat tat va trang chinh rieng.
* The san pham: khoi @media ep 9px !important la ly do tag KHONG tu thu nho
  duoc tren dien thoai. Module bo han no, thay bang don vi cqi.
* The san pham: mac dinh giu DUNG mau va kich thuoc dang chay. Bat module len
  khong doi mau gi, chi them ba viec:
  - Vien ngoai bo cong, om sat anh - cung be ngang, khong co khoang ho.
  - Gia in dam hon.
  - Hai tag dau luon nam mot hang, chat thi chu tu thu nho. Tag thu 3 nam
    rieng mot hang ben duoi.
* The san pham: chinh duoc trong trang quan tri - do bo goc, mau vien, co chu
  gia, mau gradient cua ca 3 tag, mau va chu cua badge. Tat rieng tung phan.

QUAN TRONG - thu tu khi nang cap:
  1. Cai ban nay, bat module The san pham, xem lai trang danh muc.
  2. Thay on roi MOI xoa phan trung o snippet. De nguyen se ra TAG VA BADGE DUP.
  Trang quan tri cua module ghi ro xoa khoi nao o snippet nao.

= 1.2.1 =
* The san pham: rut lai cho dung ba viec, bo het phan lam qua tay o ban 1.2.0.
  - Vien ngoai OM SAT anh san pham. Anh trai het be ngang the, khong con
    khoang ho giua vien va anh. Goc tren cua anh bi chinh cai vien cat theo.
  - Gia in dam hon.
  - Hai tag Freeship va Tra truoc luon nam tren mot hang, the bi bop hep thi
    chu trong tag tu thu nho chu khong xuong dong.
* The san pham: tra lai mau cu.
  - Tag Freeship giu nguyen mau cua web. Muon doi thi vao tick "Dung mau rieng",
    mac dinh la khong doi gi.
  - Mau gia cung vay, mac dinh giu nguyen.
* The san pham: bo phan dua "Da ban" len canh ngoi sao, ngoi sao ve cho cu.
* The san pham: khong con dong toi chieu cao the va cach cat ten san pham nua,
  de Flatsome tu can bang chieu cao nhu truoc gio. Do hai ben cung chinh mot
  chuyen nen ban 1.2.0 lam cac the cao thap so le.

= 1.2.0 =
* Module moi: THE SAN PHAM. Chinh cach trinh bay the san pham o trang danh muc,
  trang tim kiem va cac khoi goi y. Toan bo bang CSS, khong chen them the HTML
  nao, nen khong anh huong SEO va tat module di la tra ve dung nhu cu.
* Ghi chu ky thuat cho lan sau: hai tag nam mot hang nho don vi cqi - phan tram
  be ngang cua chinh THE san pham, khong phai cua man hinh. Nho vay the hep bao
  nhieu chu cung vua, khong can @media.

= 1.1.5 =
* Noi dung Danh muc: sua loi dan shortcode cua UX Builder vao thi khong hien gi.
  Nguyen nhan: dan o tab Truc quan thi WordPress boc moi dong shortcode vao mot
  the <p>. Trinh duyet khong cho the <p> chua the <div>, nen khi Flatsome dung
  ra thi cau truc vo vun va mat sach.
  - Truoc khi dung ra, plugin tu go bo nhung the <p> va <br> bi chen vao giua
    cac shortcode. Doan van co chu that van giu nguyen.
  - Chi copy mot cot [col] ma quen [row] bao ngoai thi plugin tu them [row],
    vi [col] dung mot minh Flatsome khong dung duoc.
  - Sua ngay luc dung ra, nen noi dung da dan truoc day tu chay lai duoc,
    khong phai dan lai.
* Noi dung Danh muc: chan khong cho loi do lap lai. Mo mot o co shortcode cua
  UX Builder thi khung soan tu nhay sang tab Ma va bao ro vi sao.
* Noi dung Danh muc: them nut "Tao UX Block tu noi dung nay". Dan nguyen doan
  tu UX Builder vao roi bam mot cai, plugin tao block that, gan luon vao o do
  va them vao danh sach chon cua moi danh muc. Sau nay mo bang UX Builder sua
  binh thuong, va dung lai duoc o danh muc khac.

= 1.1.4 =
* Noi dung Danh muc: thay o go chu don gian bang TRINH SOAN THAO DAY DU cua
  WordPress, dung cai ma moi nguoi da quen:
  - Hai tab Truc quan / Ma nhu khi viet bai.
  - Nut Chen tap tin mo thang Thu vien anh, keo tha anh vao cung duoc.
  - Day du dinh dang: dam, nghieng, danh sach, can le, mau chu, bang, link.
* Noi dung Danh muc: them tab XEM THU ngay trong khung soan.
  - Ban xem thu chay tren chinh giao dien web that, khong phai mo phong, nen
    font chu, mau sac, khoang cach dung y nhu khach se thay.
  - Co nut doi qua lai May tinh / Dien thoai de kiem tra ca hai.
  - Shortcode long ben trong cung duoc chay that trong ban xem thu.
* Noi dung Danh muc: ca bang chi dung MOT trinh soan thao chung. Bam vao o nao
  thi noi dung o do duoc nap vao, bam Xong thi tra nguoc lai o do. Lam vay de
  trang khong phai dung 40 trinh soan thao cung luc.
  Noi dung duoc tra ve o ngay ca khi dong bang phim Esc hay bam ra ngoai, va
  khi bam Luu toan bo luc khung soan dang mo.

* Hieu suat: do thuc te mot trang san pham chay ca 5 shortcode.
  - Truoc: doc option 9 lan moi trang. Nay: 1 lan, do nho ket qua lai trong
    cung mot lan tai trang.
  - Option chuyen sang autoload = no. Truoc day WordPress phai tai du lieu nay
    o moi request cua ca website du chi trang san pham moi can den. Ban cu tu
    doi sang che do moi mot lan khi vao trang quan tri, khong can lam gi.
  - Phan con lai moi trang: 1 truy van lay danh muc cua san pham, phan con lai
    an theo bo nho dem san co cua WordPress.
  - Noi dung tu soan NHE HON chon UX Block, vi UX Block phai di lay them mot
    bai viet nua roi moi dung ra.

= 1.1.3 =
* Noi dung Danh muc: khong con bat buoc phai ra tao UX Block moi dien duoc.
  Moi o trong bang gio co them lua chon "Tu soan tai day":
  - Bam mot cai la bat len khung soan giua man hinh, go chu, dan HTML, dan ca
    shortcode nhu [block id="..."] deu duoc.
  - Co nut Chen anh mo thang Thu vien anh cua WordPress, khong phai di copy
    duong dan anh.
  - Them nut nhanh: Chu dam, Chen link, Khung mau.
  - O nao da soan thi trong bang hien mot dong tom tat, vi du "1 anh - Giao hang
    tan noi trong 3h", nhin phat biet ngay.
  Khi nao dung cach nao: mot tam anh, mot doan chu, mot khung mau thi tu soan
  cho nhanh. Noi dung nhieu cot, nhieu hieu ung, hoac dung lai o nhieu cho thi
  van nen tao UX Block roi chon o day.
* Noi dung Danh muc: noi dung tu soan luon bi chan the script, the style, cac
  thuoc tinh onclick va duong dan javascript: truoc khi luu, ke ca voi tai
  khoan quan tri cao nhat. Tai khoan khong duoc phep dang HTML tu do thi loc
  tiep theo chuan bai viet cua WordPress.
* Noi dung Danh muc: chon "Tu soan tai day" nhung bo trong thi coi nhu chua dat,
  danh muc con van lan len lay noi dung cua cha nhu cu.

= 1.1.2 =
* Noi dung Danh muc: lam lai toan bo giao dien trang quan tri cho de thao tac.
  - Bang gon mot dong moi danh muc. Cot ten danh muc dinh ben trai, dong tieu de
    dinh ben tren khi cuon, nen khong bi mat dau moc khi bang dai.
  - Danh muc cap 1 in dam, cap duoi thut dau dong, danh muc da cau hinh co vach
    xanh ben trai. O chon nao da co noi dung thi doi mau xanh nhat.
  - Them thanh loc nhanh: go ten danh muc de tim, hoac tick "chi hien danh muc
    da cau hinh" de xem lai nhung cai da dat.
  - O chon de trong ghi san dang theo danh muc cha nao, vi du
    "- Theo cha: Box - Chi co tai SMLIFE -", khong con phai doan.
  - Nut Luu dinh o day trang, cuon den dau cung bam luu duoc.
  - 5 shortcode can dat vao layout duoc liet ke thanh the rieng ngay dau trang.
* Noi dung Danh muc: o Tinh trang chuyen thanh danh sach 5 cau co san, kem mau
  chu dung san cho tung cau:
      1. San xuat moi. Giao som 1~3 ngay!            (vang cam)
      2. Hang co san, giao Grab 3h nhan lien         (xanh)
      3. Hang co san, giao nhanh 1-3 ngay            (xanh)
      4. Hang co san (can HOA TOC goi Hotline)       (do)
      5. Hang san, thanh toan truoc giam 5%          (xanh)
  Van con lua chon "Tu nhap cau khac" kem o chon mau rieng. O nhap chi hien ra
  khi chon muc nay, khong con nam chiem cho trong bang.
  Du lieu cu luu o che do "san" tu doi sang cau so 5, khong can lam gi them.

= 1.1.1 =
* Noi dung Danh muc: lam lai theo dung mo ta layout thuc te.
  - Tu 3 slot len 5 slot:
      [smlife_tinh_trang]    1. Dong tinh trang duoi ten san pham
      [smlife_banner_sp]     2. Khoi duoi anh san pham (Tuy chinh theo yeu cau)
      [smlife_chi_co_tai]    3. Khoi CHI CO TAI SMLIFE, cot phai
      [smlife_vat_lieu]      4. Khoi mau van go / vat lieu
      [smlife_noidung_them]  5. Noi dung bo sung cuoi trang
  - Slot 2 den 5 nay CHON UX BLOCK cua Flatsome thay vi so an noi dung trong
    plugin. Noi dung van soan bang UX Builder nhu cu, muon sua chi can mo block.
  - Trang quan tri doi thanh BANG TRAI TOAN BO DANH MUC: moi danh muc mot dong,
    thut dau dong theo cap, 5 o chon tren cung mot man hinh, luu mot lan.
  - Them cai dat "An tren dien thoai" rieng cho tung slot. Mac dinh bat cho
    slot 3 vi do la cot phai chi co tren may tinh.
  - Block duoc chon nhung rong thi slot do cung khong hien.
  - Flatsome tat hoac doi theme thi slot tra ve rong, KHONG in [block id=...]
    ra giua trang san pham.
  - Du lieu ban 1.1.0 tu dong chuyen sang cau truc moi, khong mat cai dat.

= 1.1.0 =
* MODULE MOI: Noi dung Danh muc (nhom Ban hang) - cong cu dau tien cua du an
  chuan hoa layout san pham.
  - Mot layout san pham dung chung cho moi danh muc. Phan khac nhau giua cac
    danh muc soan tap trung tai SMLIFE Core > Noi dung Danh muc.
  - 3 shortcode dat vao layout dung mot lan:
      [smlife_tinh_trang]    dong Tinh trang duoi ten san pham
      [smlife_banner_sp]     banner cot phai, tu an tren dien thoai
      [smlife_noidung_them]  khoi noi dung bo sung duoi phan mo ta
  - Tinh trang co 2 mau cau chon san (Hang san giam 5% / San xuat moi 1-3 ngay)
    hoac tu nhap cau khac, chinh duoc mau chu.
  - Banner va Noi dung them soan bang trinh soan WordPress, co nut chen anh,
    va VAN NHAN SHORTCODE nen dan [block id="..."] la tai dung duoc block
    UX Builder da xay truoc day.
  - Quy tac uu tien: san pham nam o nhieu danh muc thi danh muc cap nho nhat
    thang. Phan nao danh muc con bo trong thi tu lay cua danh muc cha.
    Hai danh muc cung cap thi term_id nho hon thang, ket qua luon on dinh.
  - Danh muc khong co noi dung thi shortcode tra ve rong, trang KHONG in ma
    shortcode tho. Ke ca khi module bi tat, ban rong van duoc dang ky san.

= 1.0.11 =
* Email Setting: SUA LOI dia chi nhan hang bi dinh lien thanh mot cuc trong email don hang.
  - Nguyen nhan: WooCommerce tra dia chi duoi dang HTML ngan cach bang <br/>, code cu dung
    wp_strip_all_tags() nen xoa thang the <br/> ma khong thay bang dau ngat, thanh ra
    "Phung nha linh183 Le Thanh TonPhuong Ben ThanhThanh pho Ho Chi Minh".
  - Nay doi <br/> thanh xuong dong that roi tach tung phan, dong thoi bo ten khach ra
    khoi dia chi vi WooCommerce tu chen ten vao dong dau.
  - Token {diachi} tra ve mot dong ngan bang dau phay. Them token moi {diachi_dong}
    tra ve moi phan mot dong (duong, phuong xa, tinh thanh).
  - Bang thong tin khach trong email hien dia chi theo nhieu dong cho de doc.

= 1.0.10 =
* Form Mua ngay: shortcode [smlife_quick_order] nhung thang vao trang gio tu dien
  Ten san pham, Link san pham va Gia khi dat trong trang san pham.
  - Truoc day 3 o an nay chi duoc dien bang JS luc khach bam nut Mua ngay mo popup.
    Form nhung thang trong noi dung bai viet khong co buoc do nen gui len bi trong
    ten san pham va trong gia, dan den Google Ads khong nhan duoc gia tri don hang.
  - Nay doc thang o phia may chu bang wc_get_product(), khong phu thuoc JS.
  - Trang khong phai san pham thi de trong nhu cu, may chu van lay URL tu HTTP_REFERER.
* Dung thay cho shortcode Contact Form 7 cu o cuoi trang san pham.

= 1.0.9 =
* Lich Truc Sales: tach rieng cho chon nhan Dang truc / Ngoai ca hien o dau.
  - Them 2 cong tac: "Hien nhan o Hotline Header" va "Hien nhan o Menu Mobile".
  - MAC DINH TAT nhan o Hotline Header. Khoi hotline tren dau trang (chi thay tren may tinh)
    tu nay khong con bi chen them nhan khi co nhan vien bao nghi.
  - Menu Mobile van giu nhan nhu cu, khach van biet ai dang truc.
  - Viec lam mo va xam hotline cua nguoi ngoai ca KHONG doi, van chay o ca hai noi.
* Khong can cai dat lai gi sau khi cap nhat. Muon bat lai nhan o dau trang thi vao
  SMLIFE Core > Lich Truc Sales > muc 4 Hien thi tren website.

= 1.0.8 =
* Desktop Chat Button: BO hẳn tinh nang "Tu bung khi vao trang".
  Thanh nut luon o trang thai thu gon tren moi trang, chi bung chu khi re chuot vao.
  Khong con canh vua vao trang da bung day chu roi tu thu lai.
* Danh muc san pham: nut Loc noi tren mobile bam lai duoc.
  Nguyen nhan: snippet chua doan JS gan su kien cho nut da bi don di trong dot ra soat
  snippet, con lai moi phan HTML va CSS nen nut hien ma bam khong an.
  Da viet lai doan JS ngay trong snippet "SMLIFE - 11. Danh muc va Trang san pham (PHP)",
  chuyen tiep cu bam sang nut loc goc cua Flatsome.

= 1.0.7 =
* THEM MODULE: Desktop Chat Button (nhom Lien he va Ho tro).
  - Cum nut lien he dang thanh doc, bam sat canh phai man hinh, bo tron canh trai.
  - CHI hien tren may tinh tu moc breakpoint cai dat, dien thoai van dung Menu Mobile.
  - 6 dong nut, moi dong co ten, dong phu, link, anh icon, badge, mau nen rieng.
  - Nut "Nap lai tu Menu Mobile": lay san Zalo va Messenger da cai o module Menu Mobile.
  - Kieu Thu gon (chi thay icon tron, re chuot thi truot ra) hoac Mo san, co tu bung
    mot lan khi vao trang.
  - An theo slug trang, chinh duoc chieu cao, be rong, khoang cach, do bo goc, do lech doc.
  - La lop phu position:fixed, khong dung den chieu rong trang.
* Trang quan tri Desktop Chat Button dua ve cung kieu the nhu Promo Bar.
* Sau khi cap nhat: vao Code Snippets tat snippet "SMLIFE - 50. Desktop Chat Button".
  Cau hinh cu trong option smlife_dcb duoc giu nguyen, khong phai cai lai.

= 1.0.6 =
* Form Mua ngay: ten su kien mac dinh doi thanh form_muangay, dung ten ma trigger
  "Click - Mua Ngay" trong GTM dang nghe, nen tag GA4 va Google Ads chay lai duoc ngay.
* Form Mua ngay: neu co cau hinh chuyen trang sau khi gui, trang chi chuyen SAU KHI
  GTM ban xong tag (eventCallback, cho toi da 1,6 giay). Truoc day chuyen trang ngay
  lap tuc nen yeu cau gui ve Google Ads bi huy giua chung, chuyen doi khong duoc ghi.
* Form Mua ngay: gia tri chuyen doi lay dung gia san pham khach dang xem.

= 1.0.5 =
* Form Mua ngay: them muc "Theo doi chuyen doi (Google Tag Manager)".
  - Moi don gui thanh cong se day mot su kien len dataLayer (mac dinh ten mua_ngay).
  - Kem transaction_id duy nhat de Google khong dem trung mot don hai lan.
  - Kem so dien thoai da doi sang dang quoc te +84... va email viet thuong, dung
    ngay duoc cho Chuyen doi nang cao (Enhanced conversions) cua Google Ads.
  - Chi bay khi don di qua het buoc kiem tra that, bot dinh honeypot hoac sai
    captcha deu khong tao duoc chuyen doi ao.
  - Gia tri chuyen doi lay dung gia san pham khach dang xem (ke ca gia cua bien the
    da chon), khong con phu thuoc doan JavaScript cao gia trong GTM.
  - Ten su kien mac dinh la form_muangay, dung ten ma trigger cu trong GTM dang nghe,
    nen cac tag GA4 va Google Ads cu chay lai duoc ngay khong phai dung vao.

= 1.0.4 =
* Lich Truc Sales: don gian hoa theo cach lam that.
  - Nghi phep gio la mot nut gat: ai nghi thi gat sang Nghi, di lam lai thi gat ve Truc.
    Gat xong trang tu luu, khong can keo xuong bam Luu. Bo cach dang ky theo tung ngay.
  - Bo trang tu xep lich cho nhan su va ma PIN. Sales vao thang admin chinh.
  - Bo lich tuan co dinh. Chi con nut gat nghi va lich nghi T7 - truc CN.
  - Trang quan tri con 4 the.
* Form Mua ngay tren may tinh: khung lui xuong 42px, co bien deu hai ben va phia tren,
  dem trong khung 26px. Tren dien thoai giu nguyen nhu 1.0.3.
* SMTP: khoa dang luu hien nguyen van trong o nhap de doi chieu, khong con che bang dau cham.
  Xoa trang o do roi luu la go khoa.

= 1.0.3 =
* Lich Truc Sales (doi ten tu Lich Truc): lam lai hoan toan.
  - Dang ky nghi theo ngay: chon nguoi + tu ngay + den ngay, het han tu bat lai.
  - Lich cuoi tuan: moi tuan chon 1 nguoi truc Chu nhat, nguoi do tu dong nghi Thu 7.
  - Trang tu xep lich cho nhan su: shortcode [smlife_lich_truc] + ma PIN, nhan su mo tren
    dien thoai la tu nhan truc hoac bao nghi duoc.
  - Trang quan tri chia 6 the ro rang, co bang trang thai hom nay.
  - Lich da qua tu don, khong phinh du lieu.
* Giao dien quan tri: cac trang Giao dien Checkout, Cau truc gia, Nut Mua ngay, Form Mua ngay,
  SMTP duoc dua ve cung mot kieu the nhu Promo Bar (co dau trang, the co tieu de, bang gon).
* Form Mua ngay: khung popup nho lai (480px), an Promo Bar khi popup mo nen dau X khong con
  bi che, khoang cach giua cac dong nhap gon hon nua tren dien thoai.
* Checkout: tang co chu tu 13.5px len 14.5px cho de doc, nhan truong 13px.
* Checkout tren dien thoai: o Ghi chu don hang cao 104px, khong con bi che.

= 1.0.2 =
* Checkout: doi chu buoc 2 tren thanh tien trinh (mac dinh "Thong tin don hang"), sua trong
  SMLIFE Core > Giao dien Checkout.
* Checkout: them 3 o chon mau cho dai tieu de khu vuc (nen, chu, vach trai).
* Checkout: thu gon khoang cach giua cac o nhap, dong bo co chu giua gio hang / van chuyen /
  thanh toan, bo bot duong ke thua.
* Checkout tren dien thoai: SUA LOI cac o cot phai (Dia chi email, Phuong/Xa) bi cat ra ngoai
  man hinh. Nguyen nhan: lop mobile cu dat grid-column o muc uu tien thap hon lop goc.
* Checkout tren dien thoai: thu hep bien trang de noi dung rong them khoang 20 phan tram.
* Form Mua ngay: thu gon khoang cach cac truong tren dien thoai, popup ngan lai de nut
  Gui dat hang khong bi day khuat.
* Nut Mua ngay: them o chinh chieu cao rieng cho may tinh va dien thoai, chinh chu va mau
  nut Them vao gio, o so luong va 2 nut luon nam dung mot hang o moi be ngang man hinh.
* Menu Mobile: bang Chat cho phep danh dau kenh noi bat (mac dinh Zalo) - dua len dau bang,
  avatar to hon, co nhan rieng.

= 1.0.0 =
* Dong goi 12 module tu snippet WPCode thanh plugin hoan chinh.
* Bang dieu khien bat tat tung module.
* Mot bo giao dien admin dung chung cho moi trang.
