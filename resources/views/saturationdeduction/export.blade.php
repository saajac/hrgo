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
    @foreach($all_deductions as $deduction)
        <tr>
            <td>{{ $deduction[0] }}</td>
            <td>{{ $deduction[1] }}</td>
            <td>{{ $deduction[2] }}</td>
            <td>{{ $deduction[3] }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
