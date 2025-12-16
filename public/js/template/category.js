let subcategories = [];

function addSubcategory() {
  const input = document.getElementById("subcatInput");
  const value = input.value.trim();
  if (!value || subcategories.includes(value)) return;
  subcategories.push(value);
  updateSubcatList();
  input.value = "";
}

function updateSubcatList() {
  const container = document.getElementById("subcatList");
  container.innerHTML = "";
  subcategories.forEach((sub) => {
    const row = document.createElement("div");
    row.className = "category-row";

    const tag = document.createElement("div");
    tag.innerText = sub;

    const del = document.createElement("i");
    del.className = "fas fa-trash delete-icon";
    del.onclick = () => {
      subcategories = subcategories.filter((s) => s !== sub);
      updateSubcatList();
    };

    row.appendChild(tag);
    row.appendChild(del);
    container.appendChild(row);
  });
}

function addCategory() {
  const catName = document.getElementById("catInput").value.trim();
  if (!catName) return;
  const container = document.getElementById("catList");

  const row = document.createElement("div");
  row.className = "category-row";

  const left = document.createElement("div");
  left.className = "d-flex flex-column gap-2";

  const name = document.createElement("strong");
  name.innerText = catName;

  const tagsContainer = document.createElement("div");
  tagsContainer.className = "tags-container";
  subcategories.forEach((sub) => {
    const tag = document.createElement("span");
    tag.className = "tag-box";
    tag.innerText = sub;
    tagsContainer.appendChild(tag);
  });

  left.appendChild(name);
  left.appendChild(tagsContainer);

  const delBtn = document.createElement("i");
  delBtn.className = "fas fa-trash delete-icon";
  delBtn.onclick = () => container.removeChild(row);

  row.appendChild(left);
  row.appendChild(delBtn);
  container.appendChild(row);

  document.getElementById("catInput").value = "";
}
const tagInput = document.getElementById("tagInput");
const addTagBtn = document.getElementById("addTagBtn");
const tagsList = document.getElementById("tagsList");

const tags = ["Marketing", "Sales", "Product", "Engineering"];

function renderTags() {
  tagsList.innerHTML = "";
  tags.forEach((tag, index) => {
    const tagItem = document.createElement("div");
    tagItem.className = "tag-item";
    tagItem.innerHTML = `
                    <span>${tag}</span>
                    <button class="btn-delete" onclick="deleteTag(${index})">Delete</button>
                `;
    tagsList.appendChild(tagItem);
  });
}

function deleteTag(index) {
  tags.splice(index, 1);
  renderTags();
}

addTagBtn.addEventListener("click", () => {
  const newTag = tagInput.value.trim();
  if (newTag && !tags.includes(newTag)) {
    tags.push(newTag);
    tagInput.value = "";
    renderTags();
  }
});

renderTags();
