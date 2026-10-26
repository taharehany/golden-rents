(() => {
	"use strict";
	const $ = (s) => document.querySelector(s),
		api = "../api/";
	let csrf = "",
		page = 1,
		total = 0,
		limit = 15;
	const notice = (m, bad = false) => {
		const el = $("#notice");
		el.textContent = m;
		el.style.color = bad ? "#a22" : "#186b35";
	};
	async function request(path, options = {}) {
		const r = await fetch(api + path, {
			credentials: "same-origin",
			...options,
			headers: { "X-CSRF-Token": csrf, ...(options.headers || {}) },
		});
		const j = await r
			.json()
			.catch(() => ({ message: "Invalid server response." }));
		if (!r.ok) throw Error(j.message || "Request failed.");
		return j;
	}
	function esc(v) {
		return String(v ?? "").replace(
			/[&<>'"]/g,
			(c) =>
				({
					"&": "&amp;",
					"<": "&lt;",
					">": "&gt;",
					"'": "&#39;",
					'"': "&quot;",
				})[c],
		);
	}
	function fmt(c) {
		return `${Number(c.price).toLocaleString()} ${esc(c.currency)}`;
	}
	async function load() {
		const search = $("#search").value.trim();
		const j = await request(
			`admin/cars?page=${page}&limit=${limit}&search=${encodeURIComponent(search)}`,
		);
		total = j.meta.total;
		$("#cars").replaceChildren(
			...j.data.map((c) => {
				const tr = document.createElement("tr");
				tr.innerHTML = `<td><img src="../${esc(c.image)}" alt=""></td><td>${esc(c.name)}</td><td>${esc(c.brand)}<br>${esc(c.model)}</td><td>${fmt(c)}</td><td><span class="badge ${c.status}">${esc(c.status)}</span></td><td class="actions"><button data-edit="${c.id}">Edit</button><button data-status="${c.id}" data-value="${c.status === "published" ? "draft" : "published"}">${c.status === "published" ? "Unpublish" : "Publish"}</button><button data-delete="${c.id}">Delete</button></td>`;
				return tr;
			}),
		);
		$("#page").textContent = `Page ${page} · ${total} cars`;
		$("#prev").disabled = page === 1;
		$("#next").disabled = page * limit >= total;
	}
	async function stats() {
		const j = await request("admin/stats");
		for (const k of ["total", "published", "draft"])
			$("#" + k).textContent = j.data[k] || 0;
	}
	async function edit(id) {
		const j = await request("admin/cars/" + id),
			c = j.data,
			f = $("#car-form");
		f.reset();
		for (const k of [
			"id",
			"name",
			"brand",
			"model",
			"price",
			"currency",
			"description",
			"status",
		])
			f.elements[k].value = c[k] ?? "";
		f.elements.featured.checked = !!Number(c.featured);
		$("#preview").src = "../" + c.image;
		$("#preview").hidden = false;
		$("#form-title").textContent = "Edit car";
		f.hidden = false;
		f.scrollIntoView({ behavior: "smooth" });
	}
	function showNew() {
		const f = $("#car-form");
		f.reset();
		f.elements.id.value = "";
		f.elements.currency.value = "AED";
		$("#preview").hidden = true;
		$("#form-title").textContent = "Add car";
		f.hidden = false;
	}
	async function boot() {
		try {
			const j = await request("admin/session");
			csrf = j.data.csrf_token;
			$("#login").hidden = true;
			$("#dashboard").hidden = false;
			$("#logout").hidden = false;
			await Promise.all([stats(), load()]);
		} catch (e) {
			$("#login").hidden = false;
		}
	}
	$("#login-form").addEventListener("submit", async (e) => {
		e.preventDefault();
		try {
			const j = await request("auth/login", {
				method: "POST",
				body: new FormData(e.target),
			});
			csrf = j.data.csrf_token;
			notice("Signed in.");
			boot();
		} catch (x) {
			notice(x.message, true);
		}
	});
	$("#logout").onclick = async () => {
		try {
			await request("auth/logout", { method: "POST" });
			location.reload();
		} catch (e) {
			notice(e.message, true);
		}
	};
	$("#new-car").onclick = showNew;
	$("#cancel").onclick = () => ($("#car-form").hidden = true);
	$("#search").addEventListener("input", () => {
		page = 1;
		clearTimeout(window.searchTimer);
		window.searchTimer = setTimeout(
			() => load().catch((e) => notice(e.message, true)),
			250,
		);
	});
	$("#car-form").addEventListener("submit", async (e) => {
		e.preventDefault();
		const f = e.target,
			id = f.elements.id.value;
		try {
			await request(id ? "admin/cars/" + id : "admin/cars", {
				method: "POST",
				body: new FormData(f),
			});
			notice("Car saved.");
			f.hidden = true;
			await Promise.all([load(), stats()]);
		} catch (x) {
			notice(x.message, true);
		}
	});
	$("#car-form").elements.image.onchange = (e) => {
		const file = e.target.files[0];
		if (file) {
			$("#preview").src = URL.createObjectURL(file);
			$("#preview").hidden = false;
		}
	};
	$("#cars").onclick = async (e) => {
		const b = e.target.closest("button");
		if (!b) return;
		try {
			if (b.dataset.edit) return edit(b.dataset.edit);
			if (b.dataset.delete) {
				if (confirm("Delete this car permanently?"))
					await request("admin/cars/" + b.dataset.delete, {
						method: "DELETE",
					});
			}
			if (b.dataset.status)
				await request("admin/cars/" + b.dataset.status + "/status", {
					method: "PATCH",
					headers: { "Content-Type": "application/json" },
					body: JSON.stringify({ status: b.dataset.value }),
				});
			await Promise.all([load(), stats()]);
		} catch (x) {
			notice(x.message, true);
		}
	};
	$("#prev").onclick = () => {
		page--;
		load();
	};
	$("#next").onclick = () => {
		page++;
		load();
	};
	boot();
})();
