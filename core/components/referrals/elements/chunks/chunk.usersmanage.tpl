{var $i = 1}
<table class="table"    >
    <thead>
    <tr>
        <th scope="col">
            #
        </th>
        <th>
            Имя пользователя
        </th>
        <th>
            <a href="{($_modx->resource.id | url) ~ '?sortdir=' ~ ($newSortDir)}">Кол-во рефереалов</a>
        </th>
        <th>
            Дата регистрации
        </th>
        <th>
            Активен?
        </th>
    </tr>
    </thead>
    <tbody>
    {foreach $users as $user}
        <tr data-id="{$user.master}">
            <td scope="row">
                {$i}
            </td>
            <td>
                <a class="referralsManageShowMore" data-id="{$user.master}" style="cursor: pointer; text-decoration-line: underline;">{$user.fullname}</a>
            </td>
            <td>
                {$user.count ? $user.count : 0}
            </td>
            <td>
                {$user.createdon ? $user.createdon : '---'}
            </td>
            <td>
                {$user.active ? 'Да' : 'Нет'}
            </td>
        </tr>
        <tr class="referralsManageDetails referralsManageDetails{$user.master}" style="display: none;">
            <td scope="row" colspan="5">
                <table class="table"    >
                    <tr>
                        <td>Привел:</td>
                        <td class="referralsManageDetails{$user.master}-invited"></td>
                    </tr>
                    <tr>
                        <td>Подтвержденных:</td>
                        <td class="referralsManageDetails{$user.master}-confirmed"></td>
                    </tr>
                    <tr>
                        <td>Потратили:</td>
                        <td class="referralsManageDetails{$user.master}-paid"></td>
                    </tr>
                    <tr>
                        <td>Пользователи:</td>
                        <td class="referralsManageDetails{$user.master}-referrals"></td>
                    </tr>
                    <tr>
                        <td>Добавить пользователя</td>
                        <td>
                            <form class="form-inline referralsManageDetailsUserAddForm" data-master="{$user.master}">
                                <label class="sr-only" for="referralsManageDetailsUserAdd{$i}">E-mail</label>
                                <input type="email" name="email" class="form-control mb-2" id="referralsManageDetailsUserAdd{$i}" placeholder="e@mail.com">
                                <button type="submit" class="btn btn-primary mb-2">Поиск</button>
                                <input type="hidden" name="referrals_action" value="manage/referral/attach">
                                <input type="hidden" name="master" value="{$user.master}">
                                <input type="hidden" name="key" value="{$key}">
                            </form>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        {var $i = $i + 1}
    {/foreach}
    </tbody>
</table>
<div id="referralsManageDetailsUserTemplate" style="display: none">
    <div id="referralsManageDetailsUserRemove((user))">((fullname))<span class="referralsManageDetailsUserRemove" style="color: red" data-id="((user))"> [x] </span></div>
</div>