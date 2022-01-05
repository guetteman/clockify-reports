<table>
    <thead>
        <tr>
            <th>Project</th>
            <th>Task</th>
        </tr>
    </thead>
    @foreach($tasks as $task)
        <tr>
            <td>{{ $task['projectId'] }}</td>
            <td>{{ $task['description'] }}</td>
        </tr>
    @endforeach
</table>
