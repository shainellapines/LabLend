{{--
    A styled table. Usage:
    <x-data-table>
        <x-slot name="head"> <th>Name</th> <th>Code</th> </x-slot>
        @foreach ($rows as $row) <tr> <td>...</td> </tr> @endforeach
    </x-data-table>
    Cell padding and text styles are applied here, so pages only write plain <th> and <td>.
--}}
<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-line text-sm
                  [&_th]:px-4 [&_th]:py-3 [&_th]:text-left [&_th]:text-xs [&_th]:font-semibold [&_th]:uppercase [&_th]:tracking-wider [&_th]:text-muted
                  [&_td]:px-4 [&_td]:py-3 [&_td]:align-middle">
        <thead class="bg-canvas/60">
            <tr>{{ $head }}</tr>
        </thead>
        <tbody class="divide-y divide-line bg-white [&_tr:hover]:bg-canvas/60">
            {{ $slot }}
        </tbody>
    </table>
</div>
