// Konfirmasi untuk tombol/form berbahaya
document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (ev) {
        if (!confirm(el.dataset.confirm)) ev.preventDefault();
    });
});

// Kalkulasi otomatis di form sewa baru
(function () {
    var form = document.getElementById('form-sewa');
    if (!form) return;
    var mobil = form.querySelector('[name=mobil_id]');
    var mulai = form.querySelector('[name=tgl_mulai]');
    var selesai = form.querySelector('[name=tgl_selesai]');
    var sopir = form.querySelector('[name=pakai_sopir]');
    var biayaSopir = parseInt(form.dataset.biayaSopir, 10) || 0;
    var fmt = function (n) { return 'Rp ' + n.toLocaleString('id-ID'); };

    function hitung() {
        var opt = mobil.options[mobil.selectedIndex];
        var harga = opt ? parseInt(opt.dataset.harga, 10) || 0 : 0;
        var hari = 0;
        if (mulai.value && selesai.value) {
            hari = Math.round((new Date(selesai.value) - new Date(mulai.value)) / 86400000);
        }
        hari = Math.max(hari, 0);
        var sewa = harga * hari;
        var supir = sopir.checked ? biayaSopir * hari : 0;
        document.getElementById('r-hari').textContent = hari + ' hari';
        document.getElementById('r-sewa').textContent = fmt(sewa);
        document.getElementById('r-sopir').textContent = fmt(supir);
        document.getElementById('r-total').textContent = fmt(sewa + supir);
    }
    [mobil, mulai, selesai, sopir].forEach(function (el) { el.addEventListener('change', hitung); });
    hitung();
})();
