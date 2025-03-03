document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('password-cracker-form');
    const resultDiv = document.getElementById('results');
    const spinner = document.getElementById('spinner');

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const formData = new FormData(form);

        // Display spinner
        spinner.classList.remove('hidden')

        fetch('crack.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                // Hide spinner
                spinner.classList.add('hidden')

                // Clear previous results
                resultDiv.innerHTML = ''

                // Display new results
                if (data.length > 0) {
                    const table = document.createElement('table')
                    table.classList.add('table-auto', 'w-full', 'bg-white', 'shadow-md', 'rounded-lg', 'mt-4')

                    const thead = document.createElement('thead')
                    thead.innerHTML = `
                        <tr>
                            <th class="px-4 py-2">User Id</th>
                            <th class="px-4 py-2">Hash</th>
                            <th class="px-4 py-2">Password</th>
                        </tr>
                    `
                    table.appendChild(thead)

                    const tbody = document.createElement('tbody')
                    data.forEach(result => {
                        const row = document.createElement('tr')
                        row.innerHTML = `
                            <td class="border px-4 py-2">${result.user_id}</td>
                            <td class="border px-4 py-2">${result.password}</td>
                            <td class="border px-4 py-2">${result.actual_password}</td>
                        `
                        tbody.appendChild(row)
                    })
                    table.appendChild(tbody)

                    resultDiv.appendChild(table)
                } else {
                    resultDiv.innerHTML = '<p class="text-red-500">No results found</p>'
                }
            })
            .catch(error => {
                console.error('Error:', error)
                // Hide spinner
                spinner.classList.add('hidden')
                resultDiv.innerHTML = '<p class="text-red-500">An error occurred while processing your request</p>'
            });
    });
});
