<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f3f4f6; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="p-8">
    <div class="max-w-4xl mx-auto space-y-8">
        <div class="mb-4">
            <h1 class="text-3xl font-bold text-gray-800">Task Manager</h1>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Create New Task</h2>
            <form id="createTaskForm" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input type="text" id="title" required class="mt-1 p-2 w-full border rounded-md" placeholder="Task title">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Due Date</label>
                        <input type="date" id="due_date" required class="mt-1 p-2 w-full border rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Priority</label>
                        <select id="priority" class="mt-1 p-2 w-full border rounded-md">
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                </div>
                <div id="formError" class="text-red-500 text-sm font-medium hidden"></div>
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 font-medium transition-colors">Create Task</button>
            </form>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <div class="flex flex-col md:flex-row md:justify-between md:items-center mb-4 space-y-4 md:space-y-0">
                <h2 class="text-xl font-semibold text-gray-700">Task List</h2>
                <div class="flex space-x-2">
                    <input type="text" id="searchInput" placeholder="Search tasks..." class="p-2 border rounded-md text-sm w-full md:w-auto" oninput="renderTasks()">
                    <select id="sortPriority" class="p-2 border rounded-md text-sm" onchange="renderTasks()">
                        <option value="default">Default Sort</option>
                        <option value="high-low">Priority: High to Low</option>
                        <option value="low-high">Priority: Low to High</option>
                        <option value="date-asc">Due Date: Soonest</option>
                        <option value="date-desc">Due Date: Latest</option>
                        <option value="name-asc">Name: A to Z</option>
                        <option value="name-desc">Name: Z to A</option>
                    </select>
                </div>
            </div>
            <div id="taskList" class="space-y-4">
                <p class="text-gray-500 text-sm">Loading tasks...</p>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-md border border-gray-200">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Daily Report Tracker</h2>
            <div class="flex items-center space-x-4 mb-4">
               <input type="date" id="reportDate" class="p-2 border rounded-md text-sm">
               <button onclick="loadReport()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 font-medium transition-colors">Get Report</button>
            </div>
            <pre id="reportOutput" class="bg-gray-100 p-4 rounded text-sm text-gray-800 overflow-x-auto shadow-inner"></pre>
        </div>
    </div>

    <script>
        const API_URL = '/api/tasks';
        let allTasks = [];

        function renderTasks() {
            const list = document.getElementById('taskList');
            
            if (!allTasks || allTasks.length === 0) {
                list.innerHTML = `<p class="text-gray-500 text-sm italic">No tasks exist yet.</p>`;
                return;
            }

            const searchStr = document.getElementById('searchInput').value.toLowerCase();
            let filteredTasks = allTasks.filter(t => t.title.toLowerCase().includes(searchStr));

            const sortVal = document.getElementById('sortPriority').value;
            if (sortVal !== 'default') {
                const pMap = { 'high': 3, 'medium': 2, 'low': 1 };
                filteredTasks.sort((a, b) => {
                    if (sortVal === 'high-low') return pMap[b.priority] - pMap[a.priority];
                    if (sortVal === 'low-high') return pMap[a.priority] - pMap[b.priority];
                    if (sortVal === 'date-asc') return new Date(a.due_date) - new Date(b.due_date);
                    if (sortVal === 'date-desc') return new Date(b.due_date) - new Date(a.due_date);
                    if (sortVal === 'name-asc') return a.title.localeCompare(b.title);
                    if (sortVal === 'name-desc') return b.title.localeCompare(a.title);
                    return 0;
                });
            }

            if (filteredTasks.length === 0) {
                list.innerHTML = `<p class="text-gray-500 text-sm italic">No matching tasks found.</p>`;
                return;
            }

            list.innerHTML = filteredTasks.map(t => {
                let borderCol = t.priority === 'high' ? 'border-red-200 bg-red-50' : (t.priority === 'medium' ? 'border-yellow-200 bg-yellow-50' : 'border-blue-200 bg-blue-50');
                return `
                <div class="p-4 border rounded-md flex flex-col md:flex-row justify-between items-center ${borderCol} shadow-sm">
                    <div class="mb-4 md:mb-0 w-full md:w-auto">
                        <h3 class="font-bold text-gray-800 text-lg">${t.title}</h3>
                        <p class="text-sm text-gray-600 mt-1">Due: <span class="font-semibold text-gray-800">${t.due_date.split('T')[0]}</span> | Priority: <span class="uppercase font-semibold tracking-wide">${t.priority}</span></p>
                        <p class="text-xs text-gray-500 mt-1">Created: ${new Date(t.created_at).toLocaleString()}</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 text-xs uppercase bg-gray-200 rounded-full font-bold shadow-sm tracking-wide text-gray-700">${t.status.replace('_', ' ')}</span>
                        ${t.status !== 'done' ? 
                            `<button onclick="updateStatus(${t.id}, '${t.status === 'pending' ? 'in_progress' : 'done'}')" class="text-sm bg-gray-800 text-white px-4 py-1.5 rounded-md hover:bg-gray-700 font-medium transition-colors shadow-sm whitespace-nowrap">Move to ${t.status === 'pending' ? 'In Progress' : 'Done'}</button>` 
                            : 
                            `<button onclick="deleteTask(${t.id})" class="text-sm bg-red-600 text-white px-4 py-1.5 rounded-md hover:bg-red-700 font-medium transition-colors shadow-sm">Delete</button>`
                        }
                    </div>
                </div>
            `}).join('');
        }

        async function fetchTasks() {
            try {
                const res = await fetch(API_URL, { headers: { 'Accept': 'application/json' }});
                const tasks = await res.json();
                
                if (tasks.message) {
                    allTasks = [];
                } else {
                    allTasks = tasks;
                }
                
                renderTasks();
            } catch (e) {
                console.error(e);
            }
        }

        async function updateStatus(id, newStatus) {
            await fetch(`${API_URL}/${id}/status`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ status: newStatus })
            });
            fetchTasks();
        }

        async function deleteTask(id) {
            await fetch(`${API_URL}/${id}`, {
                method: 'DELETE',
                headers: { 'Accept': 'application/json' }
            });
            fetchTasks();
        }

        document.getElementById('createTaskForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const errDiv = document.getElementById('formError');
            errDiv.classList.add('hidden');
            
            const dueDateStr = document.getElementById('due_date').value;
            
            const now = new Date();
            const y = now.getFullYear();
            const m = String(now.getMonth() + 1).padStart(2, '0');
            const d = String(now.getDate()).padStart(2, '0');
            const localTodayStr = `${y}-${m}-${d}`;
            
            if (dueDateStr < localTodayStr) {
                errDiv.innerText = 'Validation Error: Due date cannot be in the past.';
                errDiv.classList.remove('hidden');
                return;
            }
            
            const payload = {
                title: document.getElementById('title').value,
                due_date: document.getElementById('due_date').value,
                priority: document.getElementById('priority').value
            };

            const res = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                document.getElementById('createTaskForm').reset();
                document.getElementById('due_date').valueAsDate = new Date();
                fetchTasks();
            } else {
                const err = await res.json();
                errDiv.innerText = err.message || 'Validation failed';
                errDiv.classList.remove('hidden');
            }
        });
        
        async function loadReport() {
            const date = document.getElementById('reportDate').value;
            if(!date) return;
            
            const res = await fetch(`${API_URL}/report?date=${date}`, { headers: { 'Accept': 'application/json' }});
            const data = await res.json();
            document.getElementById('reportOutput').innerText = JSON.stringify(data, null, 2);
        }

        let today = new Date();
        const y = today.getFullYear();
        const m = String(today.getMonth() + 1).padStart(2, '0');
        const d = String(today.getDate()).padStart(2, '0');
        const localTodayStr = `${y}-${m}-${d}`;
        
        document.getElementById('due_date').valueAsDate = today;
        document.getElementById('due_date').setAttribute('min', localTodayStr);
        document.getElementById('reportDate').valueAsDate = today;
        
        fetchTasks();
    </script>
</body>
</html>
