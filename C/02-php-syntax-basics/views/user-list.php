<?php
/*
 A TEMPLATE file (a "view").

 This is the ONE kind of file where mixing HTML with PHP - and therefore using
 closing tags - is correct. Notice:

   - alternative syntax (if: / endif; foreach: / endforeach;) so the HTML stays
     readable instead of being buried in curly braces
   - <?= ?> short echo tags for printing single values
   - e(...) around EVERY dynamic value, with no exceptions
   - no business logic: the controller prepared the data, the view only displays

 @var array<int, array{name: string, email: string, role: string, active: bool}> $users
 @var string $title
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($title) ?></title>
</head>
<body>
    <h1><?= e($title) ?> (<?= count($users) ?>)</h1>

    <?php if ($users === []): ?>

        <p>No users found.</p>

    <?php else: ?>

        <table border="1" cellpadding="6">
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
            </tr>

            <?php foreach ($users as $index => $user): ?>
                <tr>
                    <td><?= $index + 1 ?></td>

                    <td title="<?= e($user['name']) ?>"><?= e($user['name']) ?></td>

                    <td><a href="mailto:<?= e($user['email']) ?>"><?= e($user['email']) ?></a></td>

                    <td><?= e(ucfirst($user['role'])) ?></td>

                    <td>
                        <?php if ($user['active']): ?>
                            Active
                        <?php else: ?>
                            Suspended
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php endif; ?>
</body>
</html>
