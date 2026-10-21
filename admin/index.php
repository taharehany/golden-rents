<!doctype html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Golden Rose Admin</title>
	<link rel="stylesheet" href="admin.css">
</head>

<body>
	<main class="shell">
		<header><a href="../index.html">← View website</a>
			<h1>Golden Rose Cars</h1><button id="logout" hidden>Log out</button>
		</header>
		<p id="notice" role="status"></p>
		<section id="login">
			<h2>Admin login</h2>
			<form id="login-form"><label>Username<input name="username" autocomplete="username"
						required></label><label>Password<input type="password" name="password" autocomplete="current-password"
						required></label><button>Sign in</button></form>
		</section>
		<section id="dashboard" hidden>
			<div class="stats">
				<div>Total <b id="total">0</b></div>
				<div>Published <b id="published">0</b></div>
				<div>Drafts <b id="draft">0</b></div>
			</div>
			<div class="toolbar"><input id="search" placeholder="Search cars"><button id="new-car">Add car</button></div>
			<form id="car-form" enctype="multipart/form-data" hidden>
				<h2 id="form-title">Add car</h2><input type="hidden" name="id">
				<div class="grid"><label>Name<input name="name" required maxlength="150"></label><label>Brand<input
							name="brand" required maxlength="80"></label><label>Model / year<input name="model" required
							maxlength="80"></label><label>Price<input name="price" type="number" min="0" step=".01"
							required></label><label>Currency<input name="currency" value="AED" pattern="[A-Za-z]{3}"
							required></label><label>Status<select name="status">
							<option value="draft">Draft</option>
							<option value="published">Published</option>
						</select></label></div><label>Description<textarea name="description"></textarea></label><label><input
						name="featured" type="checkbox"> Featured</label><label>Image <input name="image" type="file"
						accept="image/jpeg,image/png,image/webp"><img id="preview" alt="Current image preview"
						hidden></label><button>Save car</button> <button type="button" id="cancel">Cancel</button>
			</form>
			<div class="table-wrap">
				<table>
					<thead>
						<tr>
							<th>Image</th>
							<th>Name</th>
							<th>Brand / model</th>
							<th>Price</th>
							<th>Status</th>
							<th>Actions</th>
						</tr>
					</thead>
					<tbody id="cars"></tbody>
				</table>
			</div>
			<div class="pager"><button id="prev">Previous</button><span id="page"></span><button id="next">Next</button>
			</div>
		</section>
	</main>
	<script src="admin.js"></script>
</body>

</html>
