function importSites() {
    Swal.fire({
        title: 'Excel İçe Aktar',
        html: `
            <div class="text-left space-y-6 mt-4">
                <div class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-2xl">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                            <i class="fa-solid fa-info-circle text-blue-400"></i>
                        </div>
                        <h4 class="text-sm font-bold text-white uppercase tracking-wider">Excel Formatı</h4>
                    </div>
                    <p class="text-[11px] text-slate-400 leading-relaxed font-medium">
                        Excel dosyanız aşağıdaki sütunları içermelidir:
                    </p>
                    <div class="mt-2 py-2 px-3 bg-white/5 rounded-xl border border-white/5 font-mono text-[10px] text-blue-300">
                        Domain | Müşteri ID | Yenileme Tarihi | Paket | Fiyat
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest ml-1">Dosya Seçin</label>
                    <div class="relative group">
                        <input type="file" id="excelFile" accept=".xlsx,.xls" 
                            class="block w-full text-sm text-slate-400
                            file:mr-4 file:py-2.5 file:px-4
                            file:rounded-xl file:border-0
                            file:text-xs file:font-bold
                            file:bg-blue-600 file:text-white
                            hover:file:bg-blue-700 transition-all
                            bg-white/5 border border-white/10 rounded-2xl p-2
                            cursor-pointer group-hover:border-blue-500/50">
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-upload mr-2"></i>Verileri Aktar',
        cancelButtonText: 'İptal',
        customClass: {
            popup: 'glass-card border-white/10 rounded-3xl',
            title: 'font-["Outfit"] text-white',
            htmlContainer: 'text-slate-400',
            confirmButton: 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl px-6 py-3 font-bold',
            cancelButton: 'bg-white/5 text-slate-300 rounded-xl px-6 py-3 font-bold hover:bg-white/10'
        },
        preConfirm: () => {
            const file = document.getElementById('excelFile').files[0];
            if (!file) {
                Swal.showValidationMessage('Lütfen bir dosya seçin');
                return false;
            }
            return file;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const file = result.value;
            const reader = new FileReader();

            Swal.fire({
                title: 'İşleniyor...',
                html: '<div class="w-12 h-12 border-4 border-blue-500/20 border-t-blue-500 rounded-full animate-spin mx-auto mb-4"></div><p class="text-slate-400">Veriler analiz ediliyor, lütfen bekleyiniz...</p>',
                showConfirmButton: false,
                allowOutsideClick: false,
                customClass: {
                    popup: 'glass-card border-white/10 rounded-3xl',
                    title: 'font-["Outfit"] text-white',
                }
            });

            reader.onload = function (e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet);

                $.post('api/sites.php', {
                    action: 'bulk_import',
                    data: JSON.stringify(jsonData)
                }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire({
                            title: 'Tamamlandı!',
                            text: `${res.imported} site başarıyla içe aktarıldı.`,
                            icon: 'success',
                            customClass: {
                                popup: 'glass-card border-white/10 rounded-3xl',
                                title: 'font-["Outfit"] text-white',
                                confirmButton: 'bg-blue-600 text-white rounded-xl font-bold px-6'
                            }
                        });
                        loadSites();
                    } else {
                        Swal.fire({
                            title: 'Hata!',
                            text: res.message,
                            icon: 'error',
                            customClass: {
                                popup: 'glass-card border-white/10 rounded-3xl',
                                title: 'font-["Outfit"] text-white'
                            }
                        });
                    }
                });
            };

            reader.readAsArrayBuffer(file);
        }
    });
}
