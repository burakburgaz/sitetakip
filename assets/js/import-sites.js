// Import Sites Function
function importSites() {
    Swal.fire({
        title: 'Excel İçe Aktar',
        html: `
            <div class="text-left space-y-4">
                <p class="text-sm text-gray-600 mb-4">Excel dosyanızdan site bilgilerini içe aktarın.</p>
                <div class="bg-blue-50 p-3 rounded mb-4">
                    <p class="text-sm font-semibold text-blue-900 mb-2">Excel Formatı:</p>
                    <p class="text-xs text-blue-700">Domain | Müşteri ID | Yenileme Tarihi | Paket | Fiyat</p>
                </div>
                <input type="file" id="excelFile" accept=".xlsx,.xls" class="w-full border rounded px-3 py-2">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-upload mr-2"></i>İçe Aktar',
        cancelButtonText: 'İptal',
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

            reader.onload = function (e) {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                const firstSheet = workbook.Sheets[workbook.SheetNames[0]];
                const jsonData = XLSX.utils.sheet_to_json(firstSheet);

                // Send to API
                $.post('api/sites.php', {
                    action: 'bulk_import',
                    data: JSON.stringify(jsonData)
                }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Başarılı!', `${res.imported} site içe aktarıldı`, 'success');
                        loadSites();
                    } else {
                        Swal.fire('Hata!', res.message, 'error');
                    }
                });
            };

            reader.readAsArrayBuffer(file);
        }
    });
}
