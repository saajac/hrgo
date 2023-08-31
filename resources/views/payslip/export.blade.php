<table>
    <thead>
        <tr>
            <th>Noms</th>
            <th>Grade</th>
            <th>Indice</th>
            <th>SalaireBase</th>
            <th>Sujet Police</th>
            <th>Mensuelle RespPart</th>
            <th>Mission Special</th>
            <th>prime</th>
            <th>Ind medecin</th>
            <th>CNR</th>
            <th>Abatt *5%</th>
            <!-- <th>MontImp</th>
            <th>RET Impot</th> -->
            <th>RET waqf</th>
            <th>Retmedical</th>
            <th>Sai Arret</th>
            <th>FONT HABITAT</th>
            <th>Ret logem</th>
            <th>RET COLLECT</th>
            <th>Ret Sub</th>
            <th>Ret foyer</th>
            <th>RET POPOTE</th>
            <th>RET A,S</th>
            <th>Retalgamil</th>
            <th>All,eau</th>
            <th>Press, Fam</th>
            <th>Pm forfaitairePFranc</th>
            <th>PFranc</th>
            <th>NET A PAYE</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employees as $employee)
        <tr>
            <td>{{ $employee['name'] }}</td>
            <td>{{ $employee['grade'] }}</td>
            <td>{{ $employee['indice'] }}</td>
            <td>{{ $employee['salary'] }}</td>
            @foreach($employee['allowance'] as $allowance)
            <td>{{ $allowance->amount }}</td>
            @endforeach
            @foreach($employee['deduction'] as $deduction)
            <td>{{ $deduction->amount }}</td>
            @endforeach
            @foreach($employee['loan'] as $loan)
            <td>{{ $loan->title }}</td>
            @endforeach
            @foreach($employee['otherPayment'] as $otherPayment)
            <td>{{ $otherPayment->amount }}</td>
            @endforeach
            <td>{{ $employee['net'] }}</td>
        </tr>
        @endforeach
    </tbody>
</table>