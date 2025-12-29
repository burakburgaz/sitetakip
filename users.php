<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_admin();
$page_title = 'Kullanıcılar - DReklam';
?>
<?php include 'includes/head.php'; ?>

<body class='bg-gray-50 flex h-screen overflow-hidden'>
    <?php include 'includes/sidebar.php'; ?>

    <div class='flex-1 flex flex-col h-screen overflow-hidden'>
        <header class='bg-white shadow-sm z-10 p-4 border-b border-gray-200'>
            <div class='flex items-center justify-between'>
                <h2 class='text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2'>
                    <i class='fa-solid fa-user-shield text-indigo-600'></i> Kullanıcı Yönetimi
                </h2>
                <button onclick='openUserModal()'
                    class='px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition'>
                    <i class='fa-solid fa-user-plus mr-2'></i>Yeni Kullanıcı Ekle
                </button>
            </div>
        </header>

        <main class='flex-1 overflow-auto p-6'>
            <div class='bg-white rounded-xl shadow-lg p-6'>
                <div id='usersTable' class='overflow-x-auto'>
                    <div class='text-center py-12'>
                        <div class='spinner mx-auto'></div>
                        <p class='mt-4 text-gray-600'>Yükleniyor...</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Kullanıcı Modal -->
    <div id='userModal'
        class='fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 modal-backdrop'>
        <div class='bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4'>
            <div class='sticky top-0 bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-6 rounded-t-2xl'>
                <div class='flex items-center justify-between'>
                    <h3 class='text-xl font-bold' id='modalTitle'>Yeni Kullanıcı Ekle</h3>
                    <button onclick='closeUserModal()' class='text-white hover:text-gray-200'>
                        <i class='fa-solid fa-times text-2xl'></i>
                    </button>
                </div>
            </div>
            <form id='userForm' class='p-6 space-y-4'>
                <input type='hidden' id='userId' name='id'>
                <input type='hidden' name='action' id='formAction' value='create'>

                <div id='usernameGroup'>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>Kullanıcı Adı *</label>
                    <input type='text' name='username' id='username' required
                        class='w-full border rounded-lg px-4 py-2'>
                </div>

                <div>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>Ad Soyad *</label>
                    <input type='text' name='name_surname' id='nameSurname' required
                        class='w-full border rounded-lg px-4 py-2'>
                </div>

                <div>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>Rol *</label>
                    <select name='role' id='role' required class='w-full border rounded-lg px-4 py-2'>
                        <option value='admin'>Admin</option>
                        <option value='secretary'>Sekreter</option>
                    </select>
                </div>

                <div>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>Telefon</label>
                    <input type='text' name='phone' id='phone' class='w-full border rounded-lg px-4 py-2'>
                </div>

                <div>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>E-posta</label>
                    <input type='email' name='email' id='email' class='w-full border rounded-lg px-4 py-2'>
                </div>

                <div>
                    <label class='block text-sm font-semibold text-gray-700 mb-2'>Şifre <span id='passwordHint'
                            class='text-xs text-gray-500'></span></label>
                    <input type='password' name='password' id='password' class='w-full border rounded-lg px-4 py-2'>
                </div>

                <div class='flex gap-3 pt-4'>
                    <button type='button' onclick='closeUserModal()'
                        class='flex-1 px-6 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition font-medium'>
                        İptal
                    </button>
                    <button type='submit'
                        class='flex-1 px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-bold'>
                        <i class='fa-solid fa-save mr-2'></i>Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        $(document).ready(function () {
            initSidebar();
            loadUsers();
        });

        function initSidebar() {
            $('#toggleSidebar').click(function () {
                const sb = $('#sidebar');
                const isExpanded = sb.hasClass('w-64');
                const texts = $('.sidebar-text');
                const icon = $(this).find('i');
                if (isExpanded) {
                    sb.removeClass('w-64').addClass('w-20');
                    texts.addClass('hidden opacity-0');
                    icon.removeClass('rotate-180');
                } else {
                    sb.removeClass('w-20').addClass('w-64');
                    setTimeout(() => texts.removeClass('hidden opacity-0'), 150);
                    icon.addClass('rotate-180');
                }
            });
        }

        function loadUsers() {
            $('#usersTable').html('<div class=\"text-center py-12\"><div class=\"spinner mx-auto\"></div><p class=\"mt-4 text-gray-600\">Yükleniyor...</p></div>');

            $.get('api/users.php', { action: 'list' }, function (res) {
                if (res.status === 'success') {
                    renderUsersTable(res.data);
                }
            });
        }

        function renderUsersTable(users) {
            if (users.length === 0) {
                $('#usersTable').html('<div class=\"text-center py-12 text-gray-500\"><i class=\"fa-solid fa-user-slash fa-3x mb-4\"></i><p>Kullanıcı bulunamadı</p></div>');
                return;
            }

            let html = '<table class=\"data-table\"><thead><tr>';
            html += '<th>Kullanıcı Adı</th><th>Ad Soyad</th><th>Rol</th><th>Telefon</th><th>E-posta</th><th>İşlemler</th>';
            html += '</tr></thead><tbody>';

            users.forEach(u => {
                const roleClass = u.role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800';
                html += `<tr>`;
                html += `<td><strong>${u.username}</strong></td>`;
                html += `<td>${u.name_surname}</td>`;
                html += `<td><span class="px-3 py-1 rounded-full text-xs font-bold ${roleClass}">${u.role === 'admin' ? 'Admin' : 'Sekreter'}</span></td>`;
                html += `<td>${u.phone || '-'}</td>`;
                html += `<td>${u.email || '-'}</td>`;
                html += `<td class="flex gap-2">`;
                html += `<button onclick="editUser(${u.id})" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"><i class="fa-solid fa-edit"></i></button>`;
                html += `<button onclick="deleteUser(${u.id})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm"><i class="fa-solid fa-trash"></i></button>`;
                html += `</td></tr>`;
            });

            html += '</tbody></table>';
            $('#usersTable').html(html);
        }

        function openUserModal() {
            $('#userForm')[0].reset();
            $('#userId').val('');
            $('#formAction').val('create');
            $('#modalTitle').text('Yeni Kullanıcı Ekle');
            $('#usernameGroup').show();
            $('#username').prop('required', true);
            $('#password').prop('required', true);
            $('#passwordHint').text('*');
            $('#userModal').removeClass('hidden').addClass('flex');
        }

        function closeUserModal() {
            $('#userModal').addClass('hidden').removeClass('flex');
        }

        function editUser(id) {
            $.get('api/users.php', { action: 'list' }, function (res) {
                if (res.status === 'success') {
                    const user = res.data.find(u => u.id == id);
                    if (user) {
                        $('#userId').val(user.id);
                        $('#formAction').val('update');
                        $('#modalTitle').text('Kullanıcı Düzenle');
                        $('#username').val(user.username);
                        $('#nameSurname').val(user.name_surname);
                        $('#role').val(user.role);
                        $('#phone').val(user.phone);
                        $('#email').val(user.email);
                        $('#usernameGroup').hide();
                        $('#password').prop('required', false);
                        $('#passwordHint').text('(Değiştirmek istemiyorsanız boş bırakın)');
                        $('#userModal').removeClass('hidden').addClass('flex');
                    }
                }
            });
        }

        function deleteUser(id) {
            Swal.fire({
                title: 'Kullanıcı Silinecek',
                text: 'Bu işlem geri alınamaz!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Evet, Sil',
                cancelButtonText: 'İptal'
            }).then(result => {
                if (result.isConfirmed) {
                    $.post('api/users.php', { action: 'delete', id: id }, function (res) {
                        if (res.status === 'success') {
                            Swal.fire('Silindi!', res.message, 'success');
                            loadUsers();
                        } else {
                            Swal.fire('Hata!', res.message, 'error');
                        }
                    });
                }
            });
        }

        $('#userForm').submit(function (e) {
            e.preventDefault();
            $.post('api/users.php', $(this).serialize(), function (res) {
                if (res.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Başarılı!', text: res.message, timer: 2000 });
                    closeUserModal();
                    loadUsers();
                } else {
                    Swal.fire('Hata!', res.message, 'error');
                }
            });
        });
    </script>
</body>

</html>