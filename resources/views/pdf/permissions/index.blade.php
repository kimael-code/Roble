@inject('exporter', 'App\Actions\Security\ExportPermissionsToPdf')

<table cellpadding="1" cellspacing="0" style="border-collapse: collapse;">
    @foreach ($permissions as $permission)
        <tr nobr="true">
            <td style="border-top: 0.5px solid #000;" width="35.5">{{ $loop->iteration }}</td>
            <td style="border-top: 0.5px solid #000;" width="163.75">{{ $permission->name }}</td>
            <td style="border-top: 0.5px solid #000;" width="254.25">{{ $permission->description }}</td>
            <td style="border-top: 0.5px solid #000;" width="106.5">{{ $permission->created_at->isoFormat('L LTS') }}</td>
            <td style="border-top: 0.5px solid #000;" width="161.5">{{ $permission->roles()->pluck('name')->implode(', ') }}</td>
            <td style="border-top: 0.5px solid #000;" width="161.5">{{ $exporter->getUsers($permission) }}</td>
        </tr>
    @endforeach
</table>
