<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>Matricule</th>
        <th>Option</th>
        <th>Amount</th>
    </tr>
    </thead>
    <tbody>
    @foreach($loans as $loan)
        <tr>
            <td>{{ $loan[0] }}</td>
            <td>{{ $loan[1] }}</td>
            <td>{{ $loan[2] }}</td>
            <td>{{ $loan[3] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
