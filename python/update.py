# Process::input(json_encode())->run(['python', 'update.py', database_path(), $name)

import sys
import json
import os
from langchain_core.documents import Document
from langchain_chroma import Chroma
from langchain_openai import OpenAIEmbeddings

# 永続化ディレクトリの設定
persist_directory = sys.argv[1] + "/chroma_db"
os.makedirs(persist_directory, exist_ok=True)

# VectorStoreの作成
embeddings = OpenAIEmbeddings(
    model="text-embedding-ada-002"
)

# VectorStoreを読み込み
vectorStore = Chroma(
    embedding_function=embeddings,
    persist_directory=persist_directory,
    collection_name=sys.argv[2],
)

# JSONファイルの読み込み
docs = []
stdin = json.loads(input().strip())
for data in stdin:
    page_content = json.dumps(data, ensure_ascii=False)
    doc_id = data.get('id')
    metadata = {'id': doc_id}

    # 既存のドキュメントをIDで検索
    existing_docs = vectorStore.get(
        where={"id": doc_id}
    )

    # 既存のドキュメントを削除
    if existing_docs and existing_docs['documents']:
        # 既存のドキュメントがある場合は削除してから追加（更新）
        vectorStore.delete(
            where={"id": doc_id}
        )
                
    docs.append(Document(
        page_content=page_content,
        metadata=metadata
    ))
        
# ドキュメントを追加（docsにデータがある場合のみ）
if docs:
    vectorStore.add_documents(docs)

# 永続化を確実に実行
vectorStore.persist()
