<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Cracker</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="js/app.js" defer></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold text-center mb-6">Password Cracker</h1>

        <form id="password-cracker-form" class="bg-white p-6 rounded-lg shadow-md w-1/2 mx-auto">
            <div class="form-group mb-4">
                <label for="type"
                    class="block text-gray-700 font-medium mb-2">
                    Select password type
                </label>
                <select name="type" id="type"
                    class="block w-full bg-gray-100 border border-gray-300 rounded py-2 px-3">
                    <option value="easy">Easy (5 numbers)</option>
                    <option value="medium-uppercase">Medium (3 Uppercase + 1 number)</option>
                    <option value="medium-lowercase">Medium (Dictionary words)</option>
                    <option value="hard">Hard (6 mixed characters)</option>
                </select>

                <button type=submit
                    class="bg-blue-500 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 mt-6">
                    Crack passwords
                </button>
            </div>
        </form>

        <div id="results" class="mt-6"></div>
    </div>
<script>

</script>
</body>
</html>
