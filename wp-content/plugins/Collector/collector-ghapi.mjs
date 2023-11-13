const state = ( Math.random() ).toString(36);
const loginButton = document.getElementById('collector-github-login');
const exportButton = document.getElementById('collector-github-export');
const importButton = document.getElementById('collector-github-import');

const getLatestCommit = async (octo, owner, repo, branch = 'main') => {
	const { data: refData } = (await octo.git.getRef({owner, repo, ref: `heads/${branch}`}));
	const commitSha = refData.object.sha
	const { data: commitData } = await octo.git.getCommit({owner, repo, commit_sha: commitSha})
	return {commitSha, treeSha: commitData.tree.sha};
};

const getRepo = async (octo, owner, repo) => {
	try {
		return (await octo.rest.repos.get({owner, repo}))?.data;
	}
	catch(error) {
		return (await octo.rest.repos.createForAuthenticatedUser({name: repo, auto_init:true}))?.data;

	};
};

const createTree = async (octo, owner, repo, tree, base_tree) => {
	const { data } = await octo.git.createTree({
		owner,
		repo,
		tree,
		base_tree,
	})

	return data;
}

const commitTree = () => {};

const initGithub = async (octo, owner, repo, branch, frameOrigin) => {

	// console.log( await getRepo(octo, owner, repo) );
	const lastCommit = await getLatestCommit(octo, owner, repo);
	console.log(lastCommit);

	const base_tree = lastCommit.treeSha;

	const exportCompleted = async data => {

		const entries = [...Object.entries(data)];
		const blobs = {};
		const texts = {};

		console.log(entries);
		const tree = [];
		let i = 0;

		for await (const [path, info] of entries) {
			let mode = '100644';
			let type = 'blob';

			if (info.mode === '040000') {
				mode = '040000';
				continue;
			}
			else if (info.mode.substr(3,1) === '7') {
				mode = '100755';
			}

			if(info.encoding !== 'base64') {
				tree.push({
					content: info.content,
					path: path.substr(1),
					mode,
				});
				continue;
			}

			await new Promise(a => setTimeout(a, 220));

			const blobData = await octo.git.createBlob({
				owner,
				repo,
				content: info.content,
				encoding: info.encoding,
			});

			console.log(path);

			tree.push({
				sha: blobData.data.sha,
				path: path.substr(1),
				mode,
				type,
			});
		};

		console.log({tree});

		const postedTree = await createTree(octo, owner, repo, tree, base_tree);

		console.log({postedTree});

		const postedCommit = await octo.git.createCommit({
			owner,
			repo,
			message: 'This is a commit message',
			tree: postedTree.sha,
			parents: [lastCommit.commitSha],
		});

		console.log({postedCommit});

		console.log(postedCommit.data.sha);

		const result = await octo.git.updateRef({
			owner,
			repo,
			ref: `heads/${branch}`,
			sha: postedCommit.data.sha,
		});

		console.log(result);
	}

	const exportButtonClicked = async event => {
		const exportComplete = event => {
			if(event.data.type !== 'exported-content-tree')
			{
				return;
			}
			window.removeEventListener('message', exportComplete, {once: true});
			console.log(event);
			exportCompleted(event.data.tree);
		};

		window.addEventListener('message', exportComplete, {once: true});

		frame.contentWindow.postMessage({type: 'export-content-tree'}, frameOrigin);
	};

	const importButtonClicked = async event => {
		frame.contentWindow.postMessage({type: 'import-content-tree'}, frameOrigin);
	};

	exportButton.addEventListener('click', exportButtonClicked);
	importButton.addEventListener('click', importButtonClicked);
}

export const initGhapi = (async (frame, frameOrigin) => {
	const { Octokit } = await import ('https://esm.sh/@octokit/rest');

	const loginButtonClicked = async event => {

		const existingJson = sessionStorage.getItem('collector-ghapi-token');

		if(existingJson)
		{
			const token = JSON.parse(existingJson);
			const octo = new Octokit({auth: token.access_token});
			const owner = 'seanmorris';
			const repo = 'wp-content-test';
			const branch = 'main';

			initGithub(octo, owner, repo, branch, frameOrigin);
			return;
		}

		const loginWindow = window.open(
			'https://github.com/login/oauth/authorize'
				+ `?redirect_uri=http://localhost:8889/wp-admin/?page=collector_github_accept`
				+ '&client_id=31539285e0c1201c4a6d'
				+ '&scope=public_repo'
				+ '&state=' + state
			, `github-login-${this.tid}`
			, `left=200,top=200,width=450,height=700,resizable=0,scrollbars=0,location=0,menubar=0,toolbar=0,status=0`
		);

		const initListener = async event => {
			if(event.data.type !== 'ghapi-token') {
				return;
			}
			window.removeEventListener('message', initListener, false);
			const token = event.data.token;
			sessionStorage.setItem('collector-ghapi-token', JSON.stringify(token));
			loginWindow.close();

			console.log(token.access_token);

			const octo = new Octokit({auth: token.access_token});
			const owner = 'seanmorris';
			const repo = 'wp-content-test';

			initGithub(octo, owner, repo, frameOrigin);
		};

		window.addEventListener('message', initListener, false);
	};


	loginButton.addEventListener('click', loginButtonClicked);

});

