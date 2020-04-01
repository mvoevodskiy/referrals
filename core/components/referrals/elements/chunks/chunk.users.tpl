{if !$users ?}
    Вы еще никого не пригласили
{else}
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
                Сумма сделок
            </th>
            <th>
                Начисления
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
        <tr>
            <td scope="row">
                {$i}
            </td>
            <td>
                {$user.fullname}
            </td>
            <td>
                {$user.cost ? $user.cost : 0}
            </td>
            <td>
                {$user.delta ? $user.delta: 0}
            </td>
            <td>
                {$user.createdon ? $user.createdon : '---'}
            </td>
            <td>
                {$user.active ? 'Да' : 'Нет'}
            </td>
        </tr>

        {var $i = $i + 1}
    {/foreach}
    </tbody>
</table>
{/if}